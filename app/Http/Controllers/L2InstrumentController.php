<?php

namespace App\Http\Controllers;

use App\Models\AuditEntry;
use App\Models\L2Instrument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class L2InstrumentController extends Controller
{
    private function admin(Request $request, ?L2Instrument $instrument = null): void
    {
        abort_unless($request->user()->role === 'bi_admin' && $request->user()->office_id, 403);
        if ($instrument) {
            abort_unless($instrument->office_id === $request->user()->office_id, 403);
        }
    }

    private function key(Request $request, string $operation): string
    {
        $nonce = $request->validate(['creation_nonce' => 'required|uuid'])['creation_nonce'];

        return hash_hmac('sha256', 'l2|'.$request->user()->office_id.'|'.$request->user()->id.'|'.$operation.'|'.$nonce, config('app.key'));
    }

    private function retry(string $key, string $hash): ?L2Instrument
    {
        $existing = L2Instrument::where('creation_key', $key)->first();
        if ($existing && ! hash_equals($existing->creation_hash, $hash)) {
            throw ValidationException::withMessages(['creation_nonce' => 'Formulir sudah digunakan untuk isi berbeda. Buka formulir baru.']);
        }

        return $existing;
    }

    private function data(Request $request): array
    {
        $data = $request->validate([
            'instructions' => 'nullable|string|max:5000',
            'questions' => 'required|array|min:1|max:'.config('lentera.l2_max_questions'),
            'questions.*' => 'required|array:text,options,correct', 'questions.*.text' => 'required|string|max:2000',
            'questions.*.options' => 'required|array:0,1,2,3|size:4', 'questions.*.options.*' => 'required|string|max:500',
            'questions.*.correct' => 'required|integer|between:0,3',
        ]);
        if (! array_is_list($data['questions'])) {
            throw ValidationException::withMessages(['questions' => 'Urutan nomor soal tidak valid.']);
        }
        $data['questions'] = array_map(fn ($question) => [...$question, 'correct' => (int) $question['correct']], $data['questions']);

        return ['questions' => $data['questions'], 'instructions' => $data['instructions'] ?? null];
    }

    private function audit(Request $request, L2Instrument $instrument, string $action): void
    {
        // Audit metadata only; do not copy answer keys into general audit pages.
        AuditEntry::create(['actor_id' => $request->user()->id, 'office_id' => $request->user()->office_id,
            'entity_type' => 'l2_instrument', 'entity_id' => $instrument->id, 'action' => $action,
            'changes' => ['after' => ['name' => $instrument->name, 'version' => $instrument->version, 'question_count' => count($instrument->questions),
                'content_hash' => hash('sha256', json_encode([$instrument->instructions, $instrument->questions], JSON_THROW_ON_ERROR)),
                'published_at' => $instrument->published_at?->toIso8601String()]], 'created_at' => now()]);
    }

    public function index(Request $request)
    {
        $this->admin($request);

        return view('l2.instruments.index', ['instruments' => L2Instrument::where('office_id', $request->user()->office_id)->latest('id')->paginate(15)]);
    }

    public function create(Request $request)
    {
        $this->admin($request);
        $count = (int) ($request->validate(['count' => 'nullable|integer|min:1|max:'.config('lentera.l2_max_questions')])['count'] ?? 5);

        return response()->view('l2.instruments.create', ['questions' => array_fill(0, $count, ['text' => '', 'options' => ['', '', '', ''], 'correct' => null]), 'nonce' => Str::uuid()->toString()])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request)
    {
        $this->admin($request);
        $name = $request->validate(['name' => 'required|string|max:160'])['name'];
        $data = $this->data($request);
        $key = $this->key($request, 'create');
        $hash = hash('sha256', json_encode([$name, $data], JSON_THROW_ON_ERROR));
        $instrument = DB::transaction(function () use ($request, $name, $data, $key, $hash) {
            DB::table('offices')->where('id', $request->user()->office_id)->lockForUpdate()->first();
            if ($existing = $this->retry($key, $hash)) {
                return $existing;
            }
            $instrument = L2Instrument::create([...$data, 'office_id' => $request->user()->office_id, 'name' => $name,
                'code' => 'L2-'.Str::uuid(), 'version' => 1, 'creation_key' => $key, 'creation_hash' => $hash]);
            $this->audit($request, $instrument, 'l2.instrument.created');

            return $instrument;
        }, 3);

        return redirect()->route('l2.instruments.show', $instrument)->with('status', 'Draf L2 tersimpan. Periksa soal dan kunci sebelum menerbitkan.');
    }

    public function show(Request $request, L2Instrument $instrument)
    {
        $this->admin($request, $instrument);

        return response()->view('l2.instruments.show', ['instrument' => $instrument, 'nonce' => Str::uuid()->toString(),
            'versions' => L2Instrument::where('code', $instrument->code)->where('office_id', $request->user()->office_id)->orderByDesc('version')->get()])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, L2Instrument $instrument)
    {
        $this->admin($request, $instrument);
        $data = $this->data($request);
        $request->validate(['revision' => 'required|integer|min:1']);
        DB::transaction(function () use ($request, $instrument, $data) {
            $locked = L2Instrument::whereKey($instrument->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->published_at, 409, 'Versi terbit terkunci. Buat versi baru.');
            $this->revision($request, $locked);
            $locked->update([...$data, 'revision' => $locked->revision + 1]);
            $this->audit($request, $locked, 'l2.instrument.updated');
        }, 3);

        return redirect()->route('l2.instruments.show', $instrument)->with('status', 'Perubahan draf L2 tersimpan.');
    }

    private function revision(Request $request, L2Instrument $instrument): void
    {
        if ((int) $request->input('revision') !== $instrument->revision) {
            throw ValidationException::withMessages(['revision' => 'Draf telah berubah. Muat ulang dan periksa isi terbaru.']);
        }
    }

    public function publish(Request $request, L2Instrument $instrument)
    {
        $this->admin($request, $instrument);
        $request->validate(['revision' => 'required|integer|min:1']);
        DB::transaction(function () use ($request, $instrument) {
            $locked = L2Instrument::whereKey($instrument->id)->lockForUpdate()->firstOrFail();
            if ($locked->published_at) {
                return;
            }
            $this->revision($request, $locked);
            if (! $locked->hasValidQuestions() || count($locked->questions) > config('lentera.l2_max_questions')) {
                throw ValidationException::withMessages(['questions' => 'Soal, empat opsi dan kunci harus lengkap sebelum terbit.']);
            }
            $locked->update(['published_at' => now(), 'revision' => $locked->revision + 1]);
            $this->audit($request, $locked, 'l2.instrument.published');
        }, 3);

        return redirect()->route('l2.instruments.show', $instrument)->with('status', 'Versi L2 diterbitkan dan terkunci.');
    }

    public function version(Request $request, L2Instrument $instrument)
    {
        $this->admin($request, $instrument);
        abort_unless($instrument->published_at, 409, 'Terbitkan draf sebelum membuat versi berikutnya.');
        $key = $this->key($request, 'version:'.$instrument->id);
        $hash = hash('sha256', (string) $instrument->id);
        $draft = DB::transaction(function () use ($request, $instrument, $key, $hash) {
            L2Instrument::where('code', $instrument->code)->where('version', 1)->lockForUpdate()->firstOrFail();
            if ($existing = $this->retry($key, $hash)) {
                return $existing;
            }
            $draft = L2Instrument::create(['office_id' => $instrument->office_id, 'code' => $instrument->code, 'name' => $instrument->name,
                'version' => L2Instrument::where('code', $instrument->code)->max('version') + 1,
                'questions' => $instrument->questions, 'instructions' => $instrument->instructions, 'creation_key' => $key, 'creation_hash' => $hash]);
            $this->audit($request, $draft, 'l2.instrument.versioned');

            return $draft;
        }, 3);

        return redirect()->route('l2.instruments.show', $draft)->with('status', 'Draf versi baru tersimpan; sesi lama tetap memakai versi sebelumnya.');
    }
}
