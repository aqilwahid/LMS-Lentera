<?php

namespace App\Http\Controllers;

use App\Actions\SubmitL1;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\L1Response;
use App\Models\LearningSession;
use App\Models\Material;
use App\Services\GuestClassAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuestClassController extends Controller
{
    public function __construct(private GuestClassAccess $access) {}

    private function joinIsOpen(LearningSession $session): bool
    {
        return $session->join_opens_at && $session->join_closes_at && now()->betweenIncluded($session->join_opens_at, $session->join_closes_at);
    }

    public function create(Request $request, string $token)
    {
        $session = $this->access->session($token);
        if ($this->access->enrollment($request, $session)) {
            return redirect()->route('class.show', $token);
        }
        $this->access->secret($request, $session);

        return view('classes.join', [
            'session' => $session, 'joinOpen' => $this->joinIsOpen($session),
            'institutions' => Institution::where('office_id', $session->activity->office_id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, string $token)
    {
        $session = $this->access->session($token);
        if ($this->access->enrollment($request, $session)) {
            return redirect()->route('class.show', $token);
        }
        abort_unless($this->joinIsOpen($session), 403, 'Pendaftaran kelas belum dibuka atau sudah ditutup.');
        $data = $request->validate([
            'full_name' => 'required|string|max:255', 'institution_name' => 'required|string|max:255',
            'identity_number' => 'required|string|max:100', 'department' => 'required|string|max:255',
            'faculty' => 'required|string|max:255', 'phone' => ['required', 'string', 'max:30', 'regex:/^[+0-9][0-9 ()-]{5,29}$/'],
            'participant_category' => 'required|string|max:100', 'email' => 'required|email|max:255',
        ], [
            'required' => ':attribute wajib diisi.', 'email' => 'Format email belum sesuai.',
            'phone.regex' => 'Nomor HP harus berisi nomor telepon yang valid.',
        ], [
            'full_name' => 'Nama lengkap', 'institution_name' => 'Instansi/Perguruan Tinggi',
            'identity_number' => 'NIP/NIM/NPM', 'department' => 'Jurusan/Program Studi',
            'faculty' => 'Fakultas', 'phone' => 'No. HP', 'participant_category' => 'Kategori', 'email' => 'Email',
        ]);
        $hash = $this->access->hash($this->access->secret($request, $session), $session);
        DB::transaction(function () use ($session, $data, $hash) {
            $locked = LearningSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->join_enabled && $locked->join_token === $session->join_token && $this->joinIsOpen($locked), 403);
            // Resolve exact master names in this KPwBI only; unknown names remain unmapped.
            $institution = Institution::where('office_id', $session->activity->office_id)->where('is_active', true)->where('name', $data['institution_name'])->first();
            $enrollment = Enrollment::firstOrCreate(['guest_access_hash' => $hash], [
                ...$data, 'email' => strtolower($data['email']), 'user_id' => null, 'status' => 'active',
                'activity_id' => $session->activity_id, 'joined_session_id' => $session->id,
                'institution_id' => $institution?->id,
            ]);
            abort_unless($enrollment->status === 'active', 403, 'Kepesertaan dinonaktifkan. Hubungi pengelola.');
        }, 3);

        return redirect()->route('class.show', $token)->with('status', 'Data peserta tersimpan. Anda sudah bergabung ke kelas.');
    }

    public function show(Request $request, string $token)
    {
        $session = $this->access->session($token);
        $enrollment = $this->access->enrollment($request, $session);
        if (! $enrollment) {
            return redirect()->route('class.join', $token);
        }

        $materials = Material::where('learning_session_id', $session->id)->where('is_visible', true)->whereNotNull('current_version_id')->with('currentVersion')->orderBy('title')->paginate(10, ['*'], 'materials_page');

        return response()->view('classes.show', compact('session', 'enrollment', 'materials'))->header('Cache-Control', 'private, no-store');
    }

    public function edit(Request $request, string $token)
    {
        $session = $this->access->session($token);
        $enrollment = $this->access->enrollment($request, $session);
        if (! $enrollment) {
            return redirect()->route('class.join', $token);
        }
        $response = L1Response::where('enrollment_id', $enrollment->id)->where('learning_session_id', $session->id)->first();

        return view('learning.l1', [
            'session' => $session, 'enrollment' => $enrollment, 'response' => $response,
            'returnUrl' => route('class.show', $token), 'submitUrl' => route('class.l1.store', $token),
        ]);
    }

    public function submit(Request $request, string $token, SubmitL1 $action)
    {
        $session = $this->access->session($token);
        $enrollment = $this->access->enrollment($request, $session);
        abort_unless($enrollment, 403, 'Isi formulir peserta melalui tautan kelas pada browser ini terlebih dahulu.');
        $action->handle($request, $enrollment, $session);

        return redirect()->route('class.l1.edit', $token)->with('status', 'Evaluasi sudah dikirim dan tersimpan di server.');
    }
}
