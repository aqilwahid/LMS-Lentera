<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AuditEntry;
use App\Models\Enrollment;
use App\Models\ImportBatch;
use App\Models\Institution;
use App\Models\L1Response;
use App\Models\LearningSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

// Historical L1 import: CSV -> staging rows -> preview -> explicit apply. Rows never auto-match by similar names.
class ImportController extends Controller
{
    private const COLUMNS = ['timestamp', 'nama', 'instansi', 'kategori', 'nim', 'email', 'nilai_1', 'nilai_2', 'nilai_3', 'nilai_4', 'nilai_5', 'masukan'];

    private const MAX_ROWS = 2000;

    private const FORMATS = ['Y-m-d H:i:s', 'Y-m-d H:i', 'd/m/Y H:i:s', 'd/m/Y H:i'];

    private function office(Request $request): int
    {
        abort_unless($request->user()->role === 'bi_admin' && $request->user()->office_id, 403);

        return $request->user()->office_id;
    }

    private function batch(Request $request, int $id): ImportBatch
    {
        return ImportBatch::where('office_id', $this->office($request))->findOrFail($id);
    }

    private function audit(Request $request, int $office, ImportBatch $batch, string $action, array $after): void
    {
        AuditEntry::create(['actor_id' => $request->user()->id, 'office_id' => $office, 'entity_type' => 'import_batch', 'entity_id' => $batch->id, 'action' => $action, 'changes' => ['before' => [], 'after' => $after], 'created_at' => now()]);
    }

    private function norm(string $v): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $v)));
    }

    private function idKey(string $nim, string $email): string
    {
        return hash('sha256', $this->norm($nim).'|'.$this->norm($email));
    }

    // Exact master names win over aliases; both only for active institutions of this office.
    private function lookup(int $office): array
    {
        $names = Institution::where('office_id', $office)->where('is_active', true)->pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [$this->norm($name) => $id])->all();
        $aliases = DB::table('institution_aliases as a')->join('institutions as i', 'i.id', '=', 'a.institution_id')
            ->where('a.office_id', $office)->where('i.is_active', true)->pluck('a.institution_id', 'a.alias')->all();

        return $names + $aliases;
    }

    private function existing(int $sessionId): array
    {
        return DB::table('l1_responses as r')->join('enrollments as e', 'e.id', '=', 'r.enrollment_id')->where('r.learning_session_id', $sessionId)
            ->get(['e.identity_number', 'e.email'])->mapWithKeys(fn ($e) => [$this->idKey((string) $e->identity_number, (string) $e->email) => true])->all();
    }

    private function time(string $v): ?Carbon
    {
        foreach (self::FORMATS as $format) {
            try {
                $t = Carbon::createFromFormat($format, $v);
            } catch (\Throwable) {
                continue;
            }
            if ($t->format($format) === $v) {
                return $t;
            }
        }

        return null;
    }

    private function ready(LearningSession $session): void
    {
        $instrument = $session->instrument;
        if (! $instrument?->published_at || ! $instrument->hasValidQuestions()) {
            throw ValidationException::withMessages(['learning_session_id' => 'Sesi belum memiliki instrumen L1 terbit yang valid.']);
        }
    }

    /** @return array{0:string,1:?string,2:array,3:?string} status, error, data, row key */
    private function classify(array $d, array &$seen, array $existing, array $lookup): array
    {
        if ($d['timestamp'] === '' && $d['email'] === '' && $d['nim'] === '') {
            return ['summary', 'Baris ringkasan/kosong dikecualikan.', $d, null];
        }
        $errors = [];
        $ts = $this->time($d['timestamp']);
        $ts || $errors[] = 'timestamp tidak dikenali (pakai 2026-04-07 10:15:00 atau 07/04/2026 10:15:00)';
        foreach (['nama', 'instansi', 'kategori', 'nim'] as $f) {
            ($d[$f] === '' || mb_strlen($d[$f]) > 255) && $errors[] = $f.' kosong atau terlalu panjang';
        }
        (! filter_var($d['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($d['email']) > 255) && $errors[] = 'email tidak valid';
        foreach (range(1, 5) as $i) {
            preg_match('/^[1-6]$/', $d["nilai_$i"]) || $errors[] = "nilai_$i harus 1–6";
        }
        mb_strlen($d['masukan']) > 2000 && $errors[] = 'masukan maksimal 2000 karakter';
        if ($errors) {
            return ['error', implode('; ', $errors), $d, null];
        }
        $d['email'] = mb_strtolower($d['email']);
        $d['submitted_at'] = $ts->format('Y-m-d H:i:s');
        $d['institution_key'] = $this->norm($d['instansi']);
        $key = $this->idKey($d['nim'], $d['email']);
        if (isset($seen[$key])) {
            return ['duplicate', 'Duplikat dalam berkas (sama dengan baris '.$seen[$key].').', $d, $key];
        }
        if (isset($existing[$key])) {
            return ['duplicate', 'Respons peserta ini sudah ada pada sesi tersebut.', $d, $key];
        }

        return isset($lookup[$d['institution_key']]) ? ['ok', null, $d, $key] : ['unmapped', 'Institusi belum dipetakan.', $d, $key];
    }

    public function index(Request $request)
    {
        $office = $this->office($request);

        return view('imports.index', [
            'batches' => ImportBatch::where('office_id', $office)->with('session')->latest('id')->paginate(10),
            'sessions' => LearningSession::whereIn('activity_id', Activity::where('office_id', $office)->select('id'))->orderByDesc('starts_at')->limit(200)->get(),
            'columns' => self::COLUMNS,
        ]);
    }

    public function store(Request $request)
    {
        $office = $this->office($request);
        $request->validate(['learning_session_id' => 'required|integer', 'file' => 'required|file|max:2048|mimes:csv,txt']);
        $session = LearningSession::whereIn('activity_id', Activity::where('office_id', $office)->select('id'))->with('instrument')->findOrFail($request->integer('learning_session_id'));
        $this->ready($session);
        $path = $request->file('file')->getRealPath();
        $raw = file_get_contents($path);
        $hash = hash('sha256', $raw);
        if (! mb_check_encoding($raw, 'UTF-8')) {
            throw ValidationException::withMessages(['file' => 'Simpan berkas sebagai CSV UTF-8.']);
        }
        if (ImportBatch::where('learning_session_id', $session->id)->where('source_hash', $hash)->exists()) {
            throw ValidationException::withMessages(['file' => 'Berkas yang sama sudah pernah dimasukkan untuk sesi ini.']);
        }
        $first = strtok(ltrim($raw, "\xEF\xBB\xBF"), "\n") ?: '';
        $delimiter = substr_count($first, ';') >= substr_count($first, ',') ? ';' : ',';
        $fh = fopen($path, 'r');
        $header = array_map(fn ($h) => mb_strtolower(trim(ltrim((string) $h, "\xEF\xBB\xBF"))), fgetcsv($fh, 0, $delimiter) ?: []);
        if ($missing = array_diff(array_diff(self::COLUMNS, ['masukan']), $header)) {
            throw ValidationException::withMessages(['file' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missing).'.']);
        }
        $lookup = $this->lookup($office);
        $existing = $this->existing($session->id);
        $seen = [];
        $rows = [];
        $line = 1;
        while (($r = fgetcsv($fh, 0, $delimiter)) !== false) {
            $line++;
            if ($r === [null]) {
                continue;
            }
            if (count($rows) >= self::MAX_ROWS) {
                throw ValidationException::withMessages(['file' => 'Maksimal '.self::MAX_ROWS.' baris per berkas; pecah berkas.']);
            }
            $d = array_map(fn ($v) => trim((string) $v), array_combine($header, array_slice(array_pad($r, count($header), ''), 0, count($header))));
            $d += ['masukan' => ''];
            [$status, $error, $d, $key] = $this->classify($d, $seen, $existing, $lookup);
            $key && $seen[$key] ??= $line;
            $rows[] = ['line' => $line, 'status' => $status, 'row_key' => $key, 'data' => json_encode(array_intersect_key($d, array_flip([...self::COLUMNS, 'submitted_at', 'institution_key'])), JSON_UNESCAPED_UNICODE), 'error' => $error];
        }
        fclose($fh);
        if (! $rows) {
            throw ValidationException::withMessages(['file' => 'Berkas tidak berisi baris data.']);
        }
        $batch = DB::transaction(function () use ($request, $office, $session, $hash, $rows) {
            $batch = ImportBatch::create(['office_id' => $office, 'learning_session_id' => $session->id, 'actor_id' => $request->user()->id, 'file_name' => mb_substr($request->file('file')->getClientOriginalName(), 0, 255), 'source_hash' => $hash]);
            foreach (array_chunk($rows, 300) as $chunk) {
                DB::table('import_rows')->insert(array_map(fn ($row) => $row + ['import_batch_id' => $batch->id], $chunk));
            }
            $this->audit($request, $office, $batch, 'import.staged', ['file_name' => $batch->file_name, 'rows' => count($rows)]);

            return $batch;
        });

        return redirect()->route('imports.show', $batch->id)->with('status', 'Berkas dibaca. Periksa pratinjau sebelum menerapkan.');
    }

    public function show(Request $request, int $id)
    {
        $batch = $this->batch($request, $id)->load('session.activity');
        $counts = DB::table('import_rows')->where('import_batch_id', $batch->id)->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');
        $unmapped = DB::table('import_rows')->where('import_batch_id', $batch->id)->where('status', 'unmapped')->pluck('data')
            ->map(fn ($j) => json_decode($j, true))->groupBy('institution_key')->map(fn ($g) => ['name' => $g[0]['instansi'], 'n' => $g->count()]);
        $held = DB::table('import_rows')->where('import_batch_id', $batch->id)->whereIn('status', ['error', 'duplicate', 'summary'])->orderBy('line')->paginate(50);
        $institutions = Institution::where('office_id', $batch->office_id)->where('is_active', true)->orderBy('name')->get();

        return view('imports.show', compact('batch', 'counts', 'unmapped', 'held', 'institutions'));
    }

    public function alias(Request $request, int $id)
    {
        $batch = $this->batch($request, $id);
        $office = $batch->office_id;
        $data = $request->validate([
            'institution_key' => 'required|string|max:255',
            'institution_id' => ['required', Rule::exists('institutions', 'id')->where('office_id', $office)->where('is_active', true)],
        ]);
        $ids = DB::table('import_rows')->where('import_batch_id', $batch->id)->where('status', 'unmapped')->get(['id', 'data'])
            ->filter(fn ($r) => json_decode($r->data, true)['institution_key'] === $data['institution_key'])->pluck('id');
        abort_if($ids->isEmpty(), 404);
        DB::transaction(function () use ($request, $batch, $office, $data, $ids) {
            DB::table('institution_aliases')->updateOrInsert(['office_id' => $office, 'alias' => $data['institution_key']], ['institution_id' => $data['institution_id'], 'updated_at' => now(), 'created_at' => now()]);
            DB::table('import_rows')->whereIn('id', $ids)->update(['status' => 'ok', 'error' => null]);
            $this->audit($request, $office, $batch, 'import.alias', ['alias' => $data['institution_key'], 'institution_id' => (int) $data['institution_id'], 'rows' => $ids->count()]);
        });

        return redirect()->route('imports.show', $batch->id)->with('status', 'Pemetaan institusi disimpan.');
    }

    public function apply(Request $request, int $id)
    {
        $batch = $this->batch($request, $id);
        $office = $batch->office_id;
        $session = LearningSession::with('instrument')->findOrFail($batch->learning_session_id);
        $this->ready($session);
        $result = DB::transaction(function () use ($request, $batch, $office, $session) {
            ImportBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $lookup = $this->lookup($office);
            $existing = $this->existing($session->id);
            $instrument = $session->instrument;
            $out = ['imported' => [], 'duplicate' => [], 'unmapped' => [], 'error' => []];
            foreach (DB::table('import_rows')->where('import_batch_id', $batch->id)->where('status', 'ok')->orderBy('line')->get() as $row) {
                $d = json_decode($row->data, true);
                $institution = $lookup[$d['institution_key']] ?? null;
                if (isset($existing[$row->row_key])) {
                    $out['duplicate'][] = $row->id;
                } elseif (! $institution) {
                    $out['unmapped'][] = $row->id;
                } else {
                    $enrollment = Enrollment::firstOrCreate(
                        ['activity_id' => $session->activity_id, 'identity_number' => $d['nim'], 'email' => $d['email']],
                        ['user_id' => null, 'institution_id' => $institution, 'institution_name' => $d['instansi'], 'participant_category' => $d['kategori'], 'status' => 'active', 'joined_session_id' => $session->id, 'full_name' => $d['nama']],
                    );
                    if ($enrollment->status !== 'active') {
                        $out['error'][] = $row->id;

                        continue;
                    }
                    $answers = array_map(fn ($i) => (int) $d["nilai_$i"], range(1, 5));
                    L1Response::create([
                        'enrollment_id' => $enrollment->id, 'learning_session_id' => $session->id, 'l1_instrument_id' => $instrument->id,
                        'instrument_snapshot' => ['code' => $instrument->code, 'version' => $instrument->version, 'questions' => $instrument->questions, 'scale' => [1, 2, 3, 4, 5, 6]],
                        'speaker_name' => $session->speaker_name, 'answers' => $answers, 'score_sum' => array_sum($answers),
                        'feedback' => $d['masukan'] !== '' ? $d['masukan'] : null, 'submitted_at' => $d['submitted_at'],
                    ]);
                    $existing[$row->row_key] = true;
                    $out['imported'][] = $row->id;
                }
            }
            $messages = ['duplicate' => 'Respons peserta ini sudah ada pada sesi tersebut.', 'unmapped' => 'Institusi belum dipetakan.', 'error' => 'Kepesertaan nonaktif.'];
            foreach ($out as $status => $ids) {
                $ids && DB::table('import_rows')->whereIn('id', $ids)->update(['status' => $status, 'error' => $messages[$status] ?? null]);
            }
            array_filter($out) && $this->audit($request, $office, $batch, 'import.applied', ['imported' => count($out['imported']), 'skipped' => count($out['duplicate']) + count($out['unmapped']) + count($out['error'])]);

            return $out;
        }, 3);

        return redirect()->route('imports.show', $batch->id)->with('status', count($result['imported']).' respons diimpor.');
    }
}
