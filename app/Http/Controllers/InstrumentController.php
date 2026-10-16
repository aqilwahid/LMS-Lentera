<?php

namespace App\Http\Controllers;

use App\Models\AuditEntry;
use App\Models\L1Instrument;
use App\Models\L1InstrumentSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InstrumentController extends Controller
{
    private function admin(Request $request): void
    {
        abort_unless($request->user()->role === 'bi_admin' && $request->user()->office_id, 403);
    }

    private function access(Request $request, L1Instrument $instrument, bool $write = false): void
    {
        $this->admin($request);
        if ($instrument->set) {
            abort_unless($instrument->set->office_id === $request->user()->office_id, 403);
        } else {
            abort_unless(! $write && $instrument->published_at, 403);
        }
    }

    private function questions(Request $request): array
    {
        return $request->validate([
            'questions' => 'required|array:0,1,2,3,4|size:5',
            'questions.*' => 'required|string|max:2000',
        ], ['questions.size' => 'Instrumen L1 harus berisi tepat lima butir.', 'questions.*.required' => 'Teks setiap butir wajib diisi.'])['questions'];
    }

    private function key(Request $request, string $operation): string
    {
        $data = $request->validate(['creation_nonce' => 'required|uuid']);

        return hash_hmac('sha256', $request->user()->office_id.'|'.$request->user()->id.'|'.$operation.'|'.$data['creation_nonce'], config('app.key'));
    }

    private function retry(string $key, string $hash): ?L1Instrument
    {
        $existing = L1Instrument::where('creation_key', $key)->first();
        if ($existing && ! hash_equals($existing->creation_hash, $hash)) {
            throw ValidationException::withMessages(['creation_nonce' => 'Formulir ini sudah digunakan untuk data berbeda. Buka formulir baru.']);
        }

        return $existing;
    }

    private function revision(Request $request, L1Instrument $instrument): void
    {
        if ((int) $request->input('revision') !== $instrument->revision) {
            throw ValidationException::withMessages(['revision' => 'Draf telah berubah. Muat ulang halaman dan periksa isi terbaru.']);
        }
    }

    private function audit(Request $request, L1Instrument $instrument, string $action, array $before = []): void
    {
        AuditEntry::create(['actor_id' => $request->user()->id, 'office_id' => $request->user()->office_id,
            'entity_type' => 'l1_instrument', 'entity_id' => $instrument->id, 'action' => $action,
            'changes' => ['before' => $before, 'after' => [
                'code' => $instrument->code, 'version' => $instrument->version,
                'questions_text' => implode("\n\n", $instrument->questions),
                'published_at' => $instrument->published_at?->toIso8601String(),
            ]], 'created_at' => now()]);
    }

    public function index(Request $request)
    {
        $this->admin($request);
        $instruments = L1Instrument::with('set')->availableToOffice($request->user()->office_id)
            ->where(fn ($q) => $q->whereNotNull('l1_instrument_set_id')->orWhereNotNull('published_at'))
            ->orderByDesc('id')->paginate(15);

        return view('instruments.index', compact('instruments'));
    }

    public function create(Request $request)
    {
        $this->admin($request);
        $request->validate(['template' => 'nullable|integer']);
        $template = $request->filled('template') ? L1Instrument::findOrFail($request->input('template')) : null;
        if ($template) {
            $this->access($request, $template);
        }

        return view('instruments.create', ['questions' => $template?->questions ?? config('lentera.l1_questions'), 'nonce' => Str::uuid()->toString()]);
    }

    public function store(Request $request)
    {
        $this->admin($request);
        $name = $request->validate(['name' => 'required|string|max:160'])['name'];
        $questions = $this->questions($request);
        $key = $this->key($request, 'create');
        $hash = hash('sha256', json_encode([$name, $questions], JSON_THROW_ON_ERROR));
        $instrument = DB::transaction(function () use ($request, $name, $questions, $key, $hash) {
            // Serialize creation retries even before the first series exists.
            DB::table('offices')->where('id', $request->user()->office_id)->lockForUpdate()->first();
            if ($existing = $this->retry($key, $hash)) {
                return $existing;
            }
            $set = L1InstrumentSet::create(['office_id' => $request->user()->office_id, 'name' => $name, 'code' => 'L1-'.Str::uuid()]);
            $instrument = $set->instruments()->create(['code' => $set->code, 'version' => 1, 'questions' => $questions, 'creation_key' => $key, 'creation_hash' => $hash]);
            $this->audit($request, $instrument, 'instrument.created');

            return $instrument;
        }, 3);

        return redirect()->route('instruments.show', $instrument)->with('status', 'Draf instrumen tersimpan. Periksa kelima butir sebelum menerbitkan.');
    }

    public function show(Request $request, L1Instrument $instrument)
    {
        $this->access($request, $instrument);
        $versions = L1Instrument::where('code', $instrument->code)->orderByDesc('version')->get();

        return view('instruments.show', ['instrument' => $instrument, 'versions' => $versions, 'nonce' => Str::uuid()->toString()]);
    }

    public function update(Request $request, L1Instrument $instrument)
    {
        $this->access($request, $instrument, true);
        $questions = $this->questions($request);
        $request->validate(['revision' => 'required|integer|min:1']);
        DB::transaction(function () use ($request, $instrument, $questions) {
            $locked = L1Instrument::whereKey($instrument->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->published_at, 409, 'Versi terbit terkunci. Buat versi baru untuk mengubah butir.');
            $this->revision($request, $locked);
            $before = ['questions_text' => implode("\n\n", $locked->questions)];
            $locked->update(['questions' => $questions, 'revision' => $locked->revision + 1]);
            $this->audit($request, $locked, 'instrument.updated', $before);
        }, 3);

        return redirect()->route('instruments.show', $instrument)->with('status', 'Perubahan draf tersimpan.');
    }

    public function publish(Request $request, L1Instrument $instrument)
    {
        $this->access($request, $instrument, true);
        $request->validate(['revision' => 'required|integer|min:1']);
        DB::transaction(function () use ($request, $instrument) {
            $locked = L1Instrument::whereKey($instrument->id)->lockForUpdate()->firstOrFail();
            if ($locked->published_at) {
                return; // Publishing again must not change the version or duplicate the audit.
            }
            $this->revision($request, $locked);
            if (! $locked->hasValidQuestions()) {
                throw ValidationException::withMessages(['questions' => 'Lengkapi tepat lima butir teks sebelum menerbitkan.']);
            }
            $locked->update(['published_at' => now(), 'revision' => $locked->revision + 1]);
            $this->audit($request, $locked, 'instrument.published');
        }, 3);

        return redirect()->route('instruments.show', $instrument)->with('status', 'Versi instrumen diterbitkan dan terkunci. Pilih versi ini pada pengaturan sesi baru.');
    }

    public function version(Request $request, L1Instrument $instrument)
    {
        $this->access($request, $instrument, true);
        abort_unless($instrument->published_at, 409, 'Simpan dan terbitkan draf ini sebelum membuat versi berikutnya.');
        $key = $this->key($request, 'version:'.$instrument->id);
        $hash = hash('sha256', (string) $instrument->id);
        $draft = DB::transaction(function () use ($request, $instrument, $key, $hash) {
            $set = L1InstrumentSet::whereKey($instrument->l1_instrument_set_id)->lockForUpdate()->firstOrFail();
            if ($existing = $this->retry($key, $hash)) {
                return $existing;
            }
            $number = (int) $set->instruments()->max('version') + 1;
            $draft = $set->instruments()->create(['code' => $set->code, 'version' => $number,
                'questions' => $instrument->questions, 'creation_key' => $key, 'creation_hash' => $hash]);
            $this->audit($request, $draft, 'instrument.versioned');

            return $draft;
        }, 3);

        return redirect()->route('instruments.show', $draft)->with('status', 'Draf versi baru tersimpan. Sesi yang sudah ada tetap memakai versi sebelumnya.');
    }
}
