<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AuditEntry;
use App\Models\Enrollment;
use App\Models\L1Instrument;
use App\Models\L1Response;
use App\Models\LearningSession;
use App\Models\Speaker;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManagementController extends Controller
{
    private function admin(Request $request): void
    {
        abort_unless($request->user()->role === 'bi_admin' && $request->user()->office_id, 403);
    }

    private function activityPermission(Request $request, Activity $activity): void
    {
        $this->admin($request);
        Gate::authorize('update', $activity);
    }

    private function audit(Request $request, string $type, int $id, string $action, array $changes): void
    {
        AuditEntry::create(['actor_id' => $request->user()->id, 'office_id' => $request->user()->office_id,
            'entity_type' => $type, 'entity_id' => $id, 'action' => $action, 'changes' => $changes, 'created_at' => now()]);
    }

    private function revision(Request $request, $model): void
    {
        if ((int) $request->input('revision') !== $model->revision) {
            throw ValidationException::withMessages(['revision' => 'Data telah diubah oleh pengelola lain atau tab lain. Muat ulang halaman sebelum menyimpan kembali.']);
        }
    }

    public function index(Request $request)
    {
        $this->admin($request);
        $activities = Activity::where('office_id', $request->user()->office_id)->withCount('sessions')->orderByDesc('id')->paginate(15);

        return view('management.index', compact('activities'));
    }

    public function create(Request $request)
    {
        $this->admin($request);

        return view('management.activity-form', ['activity' => new Activity]);
    }

    public function store(Request $request)
    {
        $this->admin($request);
        $data = $request->validate(['title' => 'required|string|max:255', 'description' => 'nullable|string|max:5000']);
        $activity = DB::transaction(function () use ($request, $data) {
            $activity = Activity::create([...$data, 'office_id' => $request->user()->office_id, 'is_demo' => config('lentera.demo_enabled')]);
            $this->audit($request, 'activity', $activity->id, 'activity.created', ['after' => $activity->only(['title', 'description'])]);

            return $activity;
        });

        return redirect()->route('management.activities.edit', $activity)->with('status', 'Kegiatan tersimpan. Tambahkan sesi untuk menyiapkan QR kelas.');
    }

    public function edit(Request $request, Activity $activity)
    {
        $this->activityPermission($request, $activity);
        $activity->load('sessions');

        return view('management.activity-form', compact('activity'));
    }

    public function update(Request $request, Activity $activity)
    {
        $this->activityPermission($request, $activity);
        $data = $request->validate(['title' => 'required|string|max:255', 'description' => 'nullable|string|max:5000', 'revision' => 'required|integer|min:1']);
        DB::transaction(function () use ($request, $activity, $data) {
            $locked = Activity::whereKey($activity->id)->lockForUpdate()->firstOrFail();
            $this->revision($request, $locked);
            $before = $locked->only(['title', 'description']);
            $locked->update(['title' => $data['title'], 'description' => $data['description'] ?? null, 'revision' => $locked->revision + 1]);
            $this->audit($request, 'activity', $locked->id, 'activity.updated', ['before' => $before, 'after' => $locked->only(['title', 'description'])]);
        }, 3);

        return redirect()->route('management.activities.edit', $activity)->with('status', 'Perubahan kegiatan tersimpan.');
    }

    private function contextLocked(LearningSession $session): bool
    {
        return Enrollment::where('joined_session_id', $session->id)->exists() || L1Response::where('learning_session_id', $session->id)->exists();
    }

    private function instruments(Request $request)
    {
        return L1Instrument::with('set')->availableToOffice($request->user()->office_id)->whereNotNull('published_at')
            ->orderBy('code')->orderByDesc('version')->get()->filter(fn ($i) => $i->hasValidQuestions());
    }

    public function createSession(Request $request, Activity $activity)
    {
        $this->activityPermission($request, $activity);

        return view('management.session-form', ['activity' => $activity, 'session' => new LearningSession, 'instruments' => $this->instruments($request), 'contextLocked' => false, 'speakers' => Speaker::where('office_id', $activity->office_id)->where('is_active', true)->orderBy('name')->get()]);
    }

    public function editSession(Request $request, LearningSession $session)
    {
        $this->activityPermission($request, $session->activity);

        return view('management.session-form', ['activity' => $session->activity, 'session' => $session, 'instruments' => $this->instruments($request), 'contextLocked' => $this->contextLocked($session), 'speakers' => Speaker::where('office_id', $session->activity->office_id)->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $session->speaker_id))->orderBy('name')->get()]);
    }

    private function sessionData(Request $request, ?LearningSession $existing = null): array
    {
        $rules = [
            'title' => 'required|string|max:255', 'speaker_name' => 'required_without:speaker_id|nullable|string|max:255',
            'speaker_id' => 'nullable|integer',
            'l1_instrument_id' => ['required', 'integer', Rule::exists('l1_instruments', 'id')->whereNotNull('published_at')],
            'starts_at' => 'required|date_format:Y-m-d\\TH:i',
            'join_opens_at' => 'required|date_format:Y-m-d\\TH:i',
            'join_closes_at' => 'required|date_format:Y-m-d\\TH:i|after:join_opens_at',
            'l1_opens_at' => 'required|date_format:Y-m-d\\TH:i',
            'l1_closes_at' => 'required|date_format:Y-m-d\\TH:i|after:l1_opens_at',
        ];
        $data = $request->validate($rules, [
            'required' => ':attribute wajib diisi.',
            'date_format' => ':attribute harus berupa tanggal dan jam yang valid.',
            'after' => ':attribute harus sesudah waktu pembukaan.',
        ], [
            'title' => 'Nama sesi', 'speaker_name' => 'Nama narasumber', 'l1_instrument_id' => 'Versi instrumen L1',
            'starts_at' => 'Waktu sesi', 'join_opens_at' => 'Pembukaan pendaftaran', 'join_closes_at' => 'Penutupan pendaftaran',
            'l1_opens_at' => 'Pembukaan L1', 'l1_closes_at' => 'Penutupan L1',
        ]);
        $instrument = L1Instrument::availableToOffice($request->user()->office_id)->find($data['l1_instrument_id']);
        if (! $instrument) {
            throw ValidationException::withMessages(['l1_instrument_id' => 'Pilih instrumen terbit dalam cakupan KPwBI Anda.']);
        }
        if (! empty($data['speaker_id'])) {
            $speaker = Speaker::where('office_id', $request->user()->office_id)->find($data['speaker_id']);
            if (! $speaker || (! $speaker->is_active && $existing?->speaker_id !== $speaker->id)) {
                throw ValidationException::withMessages(['speaker_id' => 'Pilih narasumber aktif pada KPwBI Anda.']);
            }
            $data['speaker_name'] = $existing?->speaker_id === $speaker->id ? $existing->speaker_name : $speaker->name;
        } else {
            $data['speaker_id'] = null;
        }
        if (! $instrument->hasValidQuestions()) {
            throw ValidationException::withMessages(['l1_instrument_id' => 'Instrumen L1 harus memiliki lima butir.']);
        }
        foreach (['starts_at', 'join_opens_at', 'join_closes_at', 'l1_opens_at', 'l1_closes_at'] as $field) {
            $data[$field] = Carbon::createFromFormat('!Y-m-d\\TH:i', $data[$field], config('app.timezone'));
        }

        return $data;
    }

    public function storeSession(Request $request, Activity $activity)
    {
        $this->activityPermission($request, $activity);
        $data = $this->sessionData($request);
        $session = DB::transaction(function () use ($request, $activity, $data) {
            $session = LearningSession::create([...$data, 'activity_id' => $activity->id, 'join_token' => Str::random(48), 'join_enabled' => false]);
            $this->audit($request, 'session', $session->id, 'session.created', ['after' => $session->only(array_keys($data))]);

            return $session;
        });

        return redirect()->route('management.sessions.edit', $session)->with('status', 'Sesi tersimpan. QR belum aktif; periksa jadwal lalu aktifkan akses kelas.');
    }

    public function updateSession(Request $request, LearningSession $session)
    {
        $this->activityPermission($request, $session->activity);
        $data = $this->sessionData($request, $session);
        $request->validate(['revision' => 'required|integer|min:1']);
        DB::transaction(function () use ($request, $session, $data) {
            $locked = LearningSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $this->revision($request, $locked);
            $locked->fill($data);
            if ($this->contextLocked($locked) && $locked->isDirty(['title', 'speaker_name', 'l1_instrument_id', 'speaker_id'])) {
                throw ValidationException::withMessages(['title' => 'Nama sesi, narasumber dan instrumen dikunci setelah peserta bergabung atau L1 terkirim. Buat sesi baru untuk konteks yang berbeda.']);
            }
            $before = [];
            foreach (array_keys($data) as $field) {
                $before[$field] = $locked->getRawOriginal($field);
            }
            $locked->revision++;
            $locked->save();
            $this->audit($request, 'session', $locked->id, 'session.updated', ['before' => $before, 'after' => $locked->only(array_keys($data))]);
        }, 3);

        return redirect()->route('management.sessions.edit', $session)->with('status', 'Perubahan sesi tersimpan.');
    }

    public function access(Request $request, LearningSession $session)
    {
        $this->activityPermission($request, $session->activity);
        $data = $request->validate(['revision' => 'required|integer|min:1', 'action' => ['required', Rule::in(['enable', 'disable', 'rotate'])]]);
        DB::transaction(function () use ($request, $session, $data) {
            $locked = LearningSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $this->revision($request, $locked);
            $before = ['enabled' => $locked->join_enabled];
            if ($data['action'] === 'enable') {
                abort_unless($locked->join_opens_at && $locked->join_closes_at, 409, 'Lengkapi jadwal pendaftaran terlebih dahulu.');
                $locked->join_enabled = true;
                $locked->join_token ??= Str::random(48);
            } elseif ($data['action'] === 'disable') {
                $locked->join_enabled = false;
            } else {
                $locked->join_token = Str::random(48);
            }
            $locked->revision++;
            $locked->save();
            // Tokens and participant identity are deliberately excluded from audit payloads.
            $this->audit($request, 'session', $locked->id, 'access.'.$data['action'], ['before' => $before, 'after' => ['enabled' => $locked->join_enabled], 'token_rotated' => $data['action'] === 'rotate']);
        }, 3);

        return redirect()->route('management.sessions.edit', $session)->with('status', match ($data['action']) {
            'enable' => 'Akses QR diaktifkan. Pendaftaran tetap mengikuti jadwal.',
            'disable' => 'Akses kelas dinonaktifkan. Tautan tidak dapat dipakai peserta sampai diaktifkan kembali.',
            default => 'QR dan tautan diganti. Bagikan tautan baru; tautan lama tidak berlaku.',
        });
    }

    public function auditIndex(Request $request)
    {
        $this->admin($request);
        $entries = AuditEntry::where('office_id', $request->user()->office_id)->with('actor')->orderByDesc('id')->paginate(20);

        return view('management.audit', compact('entries'));
    }
}
