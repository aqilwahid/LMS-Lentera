(() => {
    'use strict';

    const endpoint = '/admission.php';
    const ticketPattern = /^[a-f0-9]{64}$/;
    const states = new Set(['ready', 'waiting', 'disabled', 'busy', 'full', 'unavailable']);
    const staticPage = document.body.hasAttribute('data-lentera-waiting-room');
    const messages = [
        'Terima kasih sudah menunggu. Satu giliran kecil membantu teman-teman belajar bersama.',
        'Sambil menunggu, rilekskan bahu dan istirahatkan pandangan sejenak.',
        'Ingin selingan singkat? Mainkan tic-tac-toe. Giliran tetap diperiksa selama bermain.',
        'Tak perlu memuat ulang halaman. Kami akan memeriksa giliran berikutnya secara otomatis.',
    ];
    let activeJob = null;
    let ui = null;
    let gameTimer = null;

    // Keep queue credentials and unsent personal information in memory only.
    function safePath(value) {
        if (typeof value !== 'string' || !value.startsWith('/') || value.startsWith('//') || /[\\\u0000-\u0020\u007f]/.test(value)) return null;
        try {
            const url = new URL(value, location.origin);
            const decoded = decodeURIComponent(url.pathname);
            if (url.origin !== location.origin || decoded.startsWith('//') || /[\\\u0000-\u0020\u007f]/.test(decoded)) return null;
            if (/^\/(waiting-room\.html|admission\.php)(\/|$)/i.test(decoded)) return null;
            url.searchParams.delete('__admission');
            return url.pathname + url.search + url.hash;
        } catch { return null; }
    }

    function stripAdmission() {
        const url = new URL(location.href);
        if (url.searchParams.has('__admission')) {
            url.searchParams.delete('__admission');
            history.replaceState(history.state, '', url.pathname + url.search + url.hash);
        }
    }

    function retainStaticTicket(job) {
        if (!staticPage || job.form) return;
        // The fragment is never sent to the server or used for personal form data.
        const fragment = encodeURIComponent(JSON.stringify({ path: job.path, ticket: job.ticket || null }));
        history.replaceState(null, '', location.pathname + '#' + fragment);
    }

    function createUI() {
        if (ui) return ui;
        const dialog = document.createElement('dialog');
        dialog.className = 'lr-dialog';
        dialog.setAttribute('aria-labelledby', 'lr-title');
        dialog.setAttribute('aria-describedby', 'lr-description');
        dialog.innerHTML = `<div class="lr-content">
            <p class="lr-eyebrow">LENTERA · Ruang tunggu</p>
            <h2 id="lr-title">Memeriksa giliran</h2>
            <p id="lr-description">Mohon tunggu sebentar.</p>
            <div class="lr-position" id="lr-position" hidden><strong id="lr-place"></strong><span>posisi antrean saat terakhir diperiksa</span></div>
            <p id="lr-status" role="status" aria-live="polite" aria-atomic="true"></p>
            <p id="lr-next" class="lr-muted"></p>
            <p id="lr-tip" class="lr-tip"></p>
            <p id="lr-preservation" class="lr-muted"></p>
            <div class="lr-actions"><button type="button" class="lr-button" id="lr-play">Main tic-tac-toe</button><button type="button" class="lr-button lr-secondary" id="lr-cancel">Batalkan antrean</button></div>
        </div>`;
        const game = document.createElement('dialog');
        game.className = 'lr-dialog';
        game.setAttribute('aria-labelledby', 'lr-game-title');
        game.innerHTML = `<div class="lr-content"><p class="lr-eyebrow">Selingan singkat</p><h2 id="lr-game-title">Tic-tac-toe</h2>
            <p class="lr-muted">Kamu bermain sebagai X, komputer sebagai O. Susun tiga tanda sebaris. Permainan tidak mengubah giliran antrean.</p>
            <p id="lr-game-status" role="status" aria-live="polite" aria-atomic="true"></p><div class="lr-board" role="group" aria-label="Papan tic-tac-toe, 3 baris dan 3 kolom"></div>
            <div class="lr-actions"><button class="lr-button" type="button" id="lr-game-reset">Main lagi</button><button class="lr-button lr-secondary" type="button" id="lr-game-close">Kembali ke antrean</button></div></div>`;
        document.body.append(dialog, game);
        ui = { dialog, game };
        for (const id of ['title', 'description', 'position', 'place', 'status', 'next', 'tip', 'preservation', 'play', 'cancel']) ui[id] = dialog.querySelector(`#lr-${id}`);
        ui.play.addEventListener('click', () => { if (!game.open) game.showModal(); });
        ui.cancel.addEventListener('click', cancelJob);
        dialog.addEventListener('cancel', (event) => { event.preventDefault(); cancelJob(); });
        game.querySelector('#lr-game-close').addEventListener('click', () => game.close());
        setupGame(game);
        return ui;
    }

    function setupGame(game) {
        const board = game.querySelector('.lr-board');
        const status = game.querySelector('#lr-game-status');
        const winningLines = [[0, 1, 2], [3, 4, 5], [6, 7, 8], [0, 3, 6], [1, 4, 7], [2, 5, 8], [0, 4, 8], [2, 4, 6]];
        let marks = Array(9).fill('');
        let computerTurn = false;
        let finished = false;
        const cells = marks.map((_, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'lr-cell';
            button.addEventListener('click', () => move(index));
            board.append(button);
            return button;
        });
        function render() {
            const winning = winningLines.find((line) => marks[line[0]] && line.every((index) => marks[index] === marks[line[0]]));
            finished = !!winning || marks.every(Boolean);
            status.textContent = winning ? (marks[winning[0]] === 'X' ? 'Kamu menang! Main lagi sambil menunggu?' : 'Komputer menang. Yuk, coba lagi!') : finished ? 'Seri! Sama-sama hebat.' : computerTurn ? 'Komputer sedang memilih…' : 'Giliranmu. Pilih kotak kosong.';
            cells.forEach((button, index) => {
                button.textContent = marks[index];
                button.dataset.mark = marks[index];
                button.setAttribute('aria-label', `Baris ${Math.floor(index / 3) + 1}, kolom ${index % 3 + 1}: ${marks[index] || 'kosong'}`);
                button.disabled = finished || computerTurn || !!marks[index];
                button.classList.toggle('lr-winning', !!winning?.includes(index));
            });
        }
        function chooseMove() {
            for (const mark of ['O', 'X']) {
                for (const line of winningLines) {
                    const empty = line.filter((index) => !marks[index]);
                    if (empty.length === 1 && line.filter((index) => marks[index] === mark).length === 2) return empty[0];
                }
            }
            if (!marks[4]) return 4;
            const empty = marks.map((mark, index) => mark ? -1 : index).filter((index) => index >= 0);
            return empty[Math.floor(Math.random() * empty.length)];
        }
        function move(index) {
            if (finished || computerTurn || marks[index]) return;
            marks[index] = 'X';
            render();
            if (finished) return;
            computerTurn = true;
            render();
            gameTimer = setTimeout(() => {
                marks[chooseMove()] = 'O';
                computerTurn = false;
                render();
                if (game.open) cells.find((button) => !button.disabled)?.focus();
            }, 300);
        }
        game.querySelector('#lr-game-reset').addEventListener('click', () => {
            clearTimeout(gameTimer);
            marks = Array(9).fill(''); computerTurn = false; finished = false;
            render(); cells[0].focus();
        });
        game.addEventListener('close', () => {
            clearTimeout(gameTimer);
            if (computerTurn && !finished) {
                marks[chooseMove()] = 'O';
                computerTurn = false;
                render();
            }
        });
        render();
    }

    function openQueue(job) {
        const view = createUI();
        view.title.textContent = 'Memeriksa giliran';
        view.description.textContent = job.form ? 'Kami memeriksa ruang untuk memproses formulirmu.' : 'Kami memeriksa giliran untuk membuka halaman tujuanmu.';
        view.preservation.textContent = job.form ? 'Isian tetap ada di halaman ini. Formulir belum dikirim. Jangan tutup atau muat ulang tab sebelum ada konfirmasi.' : 'Tetap buka tab ini. Jika halaman dimuat ulang, giliran diperiksa kembali; posisi dapat berubah jika tiket telah kedaluwarsa.';
        view.position.hidden = true;
        view.status.textContent = 'Memeriksa ketersediaan…';
        view.tip.textContent = messages[0];
        view.next.textContent = '';
        view.cancel.disabled = false;
        view.play.disabled = false;
        if (!view.dialog.open) view.dialog.showModal();
    }

    function closeQueue() {
        if (!ui) return;
        clearTimeout(gameTimer);
        if (ui.game.open) ui.game.close();
        if (ui.dialog.open) ui.dialog.close();
    }

    function release(job) {
        if (!ticketPattern.test(job.ticket || '')) return;
        fetch(endpoint, { method: 'POST', credentials: 'same-origin', cache: 'no-store', keepalive: true, headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ action: 'cancel', ticket: job.ticket, path: job.path, method: job.method }) }).catch(() => {});
    }

    function cancelJob() {
        const job = activeJob;
        if (!job || job.sending) return;
        job.cancelled = true;
        clearTimeout(job.timer);
        job.controller?.abort();
        release(job);
        activeJob = null;
        closeQueue();
        if (job.form) job.submitter?.focus();
        else {
            const notice = document.querySelector('#lr-static-error');
            notice.hidden = false;
            notice.textContent = 'Antrean dibatalkan. Buka kembali tautan kelas dari panitia jika ingin masuk lagi.';
        }
    }

    async function request(job, url, options) {
        const controller = new AbortController();
        job.controller = controller;
        const timeout = setTimeout(() => controller.abort(), 10000);
        try { return await fetch(url, { ...options, signal: controller.signal, credentials: 'same-origin', cache: 'no-store', redirect: 'error' }); }
        finally { clearTimeout(timeout); if (job.controller === controller) job.controller = null; }
    }

    function schedule(job, seconds, outage = false) {
        if (activeJob !== job || job.cancelled) return;
        const serverDelay = Number.isFinite(Number(seconds)) ? Math.max(2, Number(seconds)) : 10;
        const base = outage ? Math.min(60, Math.max(serverDelay, 5 * 2 ** Math.min(job.failures, 4))) : serverDelay;
        const delay = outage ? Math.min(60, base * (1 + Math.random() * .3)) : base * (1 + Math.random() * .3);
        ui.next.textContent = `Pemeriksaan berikutnya dalam sekitar ${Math.ceil(delay)} detik. Waktu masuk bergantung pada ketersediaan layanan.`;
        clearTimeout(job.timer);
        job.timer = setTimeout(() => poll(job), delay * 1000);
    }

    function displayWaiting(job, data) {
        const hasPosition = data.state === 'waiting' && Number.isSafeInteger(data.position) && data.position > 0;
        ui.position.hidden = !hasPosition;
        if (hasPosition) ui.place.textContent = String(data.position);
        ui.title.textContent = data.state === 'full' ? 'Antrean sedang penuh' : data.state === 'unavailable' ? 'Koneksi layanan belum siap' : 'Sebentar, giliranmu akan tiba';
        ui.status.textContent = data.state === 'full' ? 'Belum mendapat tempat antrean. Kami akan mencoba memeriksa kembali.' : data.state === 'unavailable' ? 'Layanan belum dapat mengonfirmasi giliran. Kami mencoba lagi dengan jeda.' : data.state === 'busy' ? 'Layanan sedang mengatur giliran. Posisi antrean belum tersedia.' : hasPosition ? `Posisi antreanmu saat ini ${data.position}.` : 'Menunggu ruang pemrosesan. Posisi antrean belum tersedia.';
        ui.tip.textContent = messages[job.checks % messages.length];
        schedule(job, data.retry_after, data.state === 'unavailable');
    }

    async function poll(job) {
        if (activeJob !== job || job.cancelled || job.polling || job.sending) return;
        if (!navigator.onLine) {
            ui.title.textContent = 'Koneksi internet terputus';
            ui.status.textContent = 'Pemeriksaan dijeda. Sambungkan kembali internet agar antrean dapat dilanjutkan.';
            ui.next.textContent = 'Isian yang belum dikirim tetap ada selama tab ini terbuka.';
            return;
        }
        job.polling = true;
        try {
            const response = await request(job, endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ path: job.path, method: job.method, ticket: job.ticket || null }) });
            const data = await response.json();
            if (activeJob !== job || job.cancelled) return;
            if (!states.has(data.state)) throw new Error('Invalid admission response');
            if (ticketPattern.test(data.ticket || '')) {
                job.ticket = data.ticket;
                retainStaticTicket(job);
            }
            job.checks++;
            job.failures = data.state === 'unavailable' ? job.failures + 1 : 0;
            if (data.state === 'ready' || data.state === 'disabled') {
                if (data.state === 'ready' && !ticketPattern.test(job.ticket || '')) throw new Error('Admission ticket missing');
                job.polling = false;
                await proceed(job, data.state === 'ready');
            } else displayWaiting(job, data);
        } catch {
            if (activeJob !== job || job.cancelled) return;
            job.failures++;
            displayWaiting(job, { state: 'unavailable', retry_after: 5 });
        } finally { job.polling = false; }
    }

    function showFormError(job, message, errors = {}) {
        const form = job.form;
        let region = form.querySelector('[data-lentera-form-error]');
        if (!region) {
            region = document.createElement('div');
            region.className = 'lr-error';
            region.dataset.lenteraFormError = '';
            region.tabIndex = -1;
            region.setAttribute('role', 'alert');
            form.prepend(region);
        }
        region.replaceChildren();
        const title = document.createElement('h2');
        title.textContent = message;
        region.append(title);
        const entries = Object.entries(errors).slice(0, 100);
        if (entries.length) {
            const list = document.createElement('ul');
            entries.forEach(([field, messages]) => {
                const item = document.createElement('li');
                item.textContent = (Array.isArray(messages) ? messages : [messages]).filter((value) => typeof value === 'string').join(' ');
                list.append(item);
                const inputName = field.replace(/\.([^.]*)/g, '[$1]');
                for (const control of form.elements) if (control.name === inputName || control.name === field) control.setAttribute('aria-invalid', 'true');
            });
            region.append(list);
        } else {
            const detail = document.createElement('p');
            detail.textContent = 'Isian tetap ada di halaman ini. Periksa status melalui halaman kelas sebelum mencoba mengirim lagi jika hasil pengiriman belum pasti.';
            region.append(detail);
        }
        activeJob = null;
        closeQueue();
        region.focus();
        region.scrollIntoView({ block: 'center', behavior: 'instant' });
    }

    async function proceed(job, admitted) {
        if (activeJob !== job || job.cancelled) return;
        clearTimeout(gameTimer);
        if (!job.form) {
            activeJob = null;
            const target = new URL(job.path, location.origin);
            if (admitted) target.searchParams.set('__admission', job.ticket);
            location.replace(target.pathname + target.search + target.hash);
            return;
        }
        job.sending = true;
        if (ui.game.open) ui.game.close();
        ui.title.textContent = 'Sedang mengirim formulir';
        ui.status.textContent = 'Tunggu konfirmasi sebelum menutup halaman.';
        ui.preservation.textContent = 'Pengiriman sedang diproses. Jangan memuat ulang tab.';
        ui.position.hidden = true;
        ui.next.textContent = '';
        ui.cancel.disabled = true;
        ui.play.disabled = true;
        try {
            const response = await request(job, job.path, { method: job.method, headers: { Accept: 'application/json', 'X-Lentera-Async': '1', ...(admitted ? { 'X-Lentera-Ticket': job.ticket } : {}) }, body: job.data });
            const data = await response.json().catch(() => null);
            if (response.headers.get('X-Lentera-Admission') === 'waiting' && data && states.has(data.state)) {
                // This header certifies that Laravel did not process the POST.
                job.sending = false;
                if (ticketPattern.test(data.ticket || '')) job.ticket = data.ticket;
                ui.cancel.disabled = false;
                ui.play.disabled = false;
                ui.preservation.textContent = 'Formulir belum diproses. Isian tetap ada di halaman ini sambil menunggu giliran berikutnya.';
                displayWaiting(job, data);
                return;
            }
            if (response.ok && data && typeof data.redirect === 'string') {
                const destination = new URL(data.redirect, location.href);
                const next = destination.origin === location.origin ? safePath(destination.pathname + destination.search + destination.hash) : null;
                if (!next) throw new Error('Invalid destination');
                activeJob = null;
                location.assign(next);
                return;
            }
            if (response.status === 422 && data?.errors && typeof data.errors === 'object') {
                showFormError(job, 'Periksa kembali isian berikut.', data.errors);
            } else if (response.status === 419) {
                showFormError(job, 'Sesi halaman sudah berakhir. Salin isian penting sebelum membuka ulang halaman.');
            } else if ([401, 403, 404, 409, 429].includes(response.status)) {
                showFormError(job, typeof data?.message === 'string' ? data.message : 'Formulir belum berhasil diproses. Periksa akses atau status kelas lalu coba lagi.');
            } else {
                showFormError(job, 'Konfirmasi pengiriman belum diterima. Formulir mungkin sudah diproses; periksa status sebelum mencoba lagi.');
            }
        } catch {
            showFormError(job, 'Konfirmasi pengiriman belum diterima. Formulir mungkin sudah diproses; periksa status sebelum mencoba lagi.');
        } finally { job.sending = false; }
    }

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        const submitter = event.submitter;
        const method = (submitter?.getAttribute('formmethod') || form.getAttribute('method') || 'get').toUpperCase();
        if (method !== 'POST') return;
        const action = new URL(submitter?.getAttribute('formaction') || form.getAttribute('action') || location.href, location.href);
        if (action.origin !== location.origin) return;
        const path = safePath(action.pathname + action.search);
        if (!path) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        if (activeJob) { if (ui && !ui.dialog.open) ui.dialog.showModal(); return; }
        form.querySelector('[data-lentera-form-error]')?.remove();
        for (const control of form.elements) control.removeAttribute('aria-invalid');
        const data = new FormData(form);
        if (submitter?.name) data.append(submitter.name, submitter.value);
        const job = { form, submitter, data, path, method, ticket: null, checks: 0, failures: 0, cancelled: false, sending: false, polling: false };
        activeJob = job;
        openQueue(job);
        poll(job);
    }, true);

    window.addEventListener('online', () => {
        if (!activeJob || activeJob.sending) return;
        clearTimeout(activeJob.timer);
        // Reconnecting a crowd should not cause a synchronized retry burst.
        schedule(activeJob, 2);
    });
    window.addEventListener('offline', () => {
        if (!activeJob || activeJob.sending) return;
        clearTimeout(activeJob.timer);
        ui.status.textContent = 'Koneksi terputus. Pemeriksaan dilanjutkan setelah internet tersambung kembali.';
        ui.next.textContent = 'Jangan tutup tab agar isian yang belum dikirim tetap tersedia.';
    });
    window.addEventListener('beforeunload', (event) => {
        if (activeJob?.form) { event.preventDefault(); event.returnValue = ''; }
    });

    stripAdmission();
    if (staticPage) {
        try {
            const payload = JSON.parse(decodeURIComponent(location.hash.slice(1)));
            const path = safePath(payload.path);
            if (!path) throw new Error('Invalid path');
            const job = { path, method: 'GET', ticket: ticketPattern.test(payload.ticket || '') ? payload.ticket : null, checks: 0, failures: 0, cancelled: false, sending: false, polling: false };
            retainStaticTicket(job);
            activeJob = job;
            openQueue(job);
            poll(job);
        } catch {
            const notice = document.querySelector('#lr-static-error');
            notice.hidden = false;
            notice.textContent = 'Tautan antrean tidak lengkap atau tidak valid. Buka kembali tautan kelas dari panitia.';
        }
    }
})();
