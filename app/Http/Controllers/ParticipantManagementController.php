<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AuditEntry;
use App\Models\Enrollment;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ParticipantManagementController extends Controller
{
    private function admin(Request $request): void
    {
        abort_unless($request->user()->role === 'bi_admin' && $request->user()->office_id, 403);
    }

    private function record(Request $request, Enrollment $enrollment): void
    {
        $this->admin($request);
        abort_unless($enrollment->activity->office_id === $request->user()->office_id, 403);
    }

    public function index(Request $request)
    {
        $this->admin($request);
        $office = $request->user()->office_id;
        $data = $request->validate(['q' => 'nullable|string|max:100', 'activity_id' => ['nullable', 'integer', Rule::exists('activities', 'id')->where('office_id', $office)], 'mapping' => ['nullable', Rule::in(['all', 'unmapped'])]]);
        $query = Enrollment::whereHas('activity', fn ($q) => $q->where('office_id', $office))->with(['activity', 'joinedSession', 'user', 'mappedInstitution']);
        if ($data['q'] ?? null) {
            $term = '%'.$data['q'].'%';
            $query->where(fn ($q) => $q->where('full_name', 'like', $term)->orWhere('institution_name', 'like', $term)->orWhere('identity_number', 'like', $term));
        }
        if ($data['activity_id'] ?? null) {
            $query->where('activity_id', $data['activity_id']);
        }
        if (($data['mapping'] ?? 'all') === 'unmapped') {
            $query->whereNull('institution_id')->whereNull('mapped_institution_id');
        }

        return response()->view('participants.index', [
            'enrollments' => $query->orderByDesc('id')->paginate(15)->withQueryString(),
            'activities' => Activity::where('office_id', $office)->orderBy('title')->get(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request, Enrollment $enrollment)
    {
        $this->record($request, $enrollment);

        return response()->view('participants.show', [
            'enrollment' => $enrollment->load(['joinedSession', 'user', 'mappedInstitution']),
            'institutions' => Institution::where('office_id', $request->user()->office_id)->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $enrollment->mapped_institution_id))->orderBy('name')->get(),
        ])->header('Cache-Control', 'private, no-store');
    }

    // Contact/identity typos only; original institution and category stay fixed because aggregates depend on them.
    public function correct(Request $request, Enrollment $enrollment)
    {
        $this->record($request, $enrollment);
        $data = $request->validate([
            'revision' => 'required|integer|min:1',
            'full_name' => 'required|string|max:255', 'identity_number' => 'required|string|max:100',
            'email' => 'required|email|max:255', 'phone' => ['required', 'string', 'max:30', 'regex:/^[+0-9][0-9 ()-]{5,29}$/'],
            'department' => 'required|string|max:255', 'faculty' => 'required|string|max:255',
            'reason' => 'required|string|min:5|max:500',
        ], ['phone.regex' => 'Nomor HP harus berisi nomor telepon yang valid.', 'reason.required' => 'Alasan koreksi wajib diisi.']);
        $data['email'] = strtolower(trim($data['email']));
        $fields = ['full_name', 'identity_number', 'email', 'phone', 'department', 'faculty'];
        DB::transaction(function () use ($request, $enrollment, $data, $fields) {
            $locked = Enrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            if ($locked->revision !== (int) $data['revision']) {
                throw ValidationException::withMessages(['revision' => 'Kepesertaan telah diubah. Muat ulang halaman sebelum menyimpan.']);
            }
            $new = array_map('trim', array_intersect_key($data, array_flip($fields)));
            $before = $locked->only($fields);
            $changed = array_keys(array_diff_assoc($new, $before));
            if (! $changed) {
                throw ValidationException::withMessages(['full_name' => 'Tidak ada data yang berubah.']);
            }
            $locked->forceFill($new);
            $locked->revision++;
            $locked->save();
            AuditEntry::create(['actor_id' => $request->user()->id, 'office_id' => $request->user()->office_id, 'entity_type' => 'enrollment', 'entity_id' => $locked->id,
                'action' => 'enrollment.corrected', 'changes' => ['before' => Arr::only($before, $changed), 'after' => Arr::only($new, $changed) + ['reason' => $data['reason']]], 'created_at' => now()]);
        }, 3);

        return redirect()->route('participants.show', $enrollment)->with('status', 'Koreksi identitas tersimpan dan dicatat pada riwayat.');
    }

    public function update(Request $request, Enrollment $enrollment)
    {
        $this->record($request, $enrollment);
        $office = $request->user()->office_id;
        $data = $request->validate([
            'revision' => 'required|integer|min:1',
            'status' => ['required', Rule::in(['active', 'revoked'])],
            'mapped_institution_id' => ['nullable', 'integer', Rule::exists('institutions', 'id')->where('office_id', $office)->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $enrollment->mapped_institution_id))],
        ]);
        DB::transaction(function () use ($request, $enrollment, $data, $office) {
            $locked = Enrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            if ($locked->revision !== (int) $data['revision']) {
                throw ValidationException::withMessages(['revision' => 'Kepesertaan telah diubah. Muat ulang halaman sebelum menyimpan.']);
            }
            $mapping = isset($data['mapped_institution_id']) ? (int) $data['mapped_institution_id'] : null;
            if ($locked->institution_id && $mapping) {
                throw ValidationException::withMessages(['mapped_institution_id' => 'Institusi sudah dikenali saat pendaftaran. Konteks historis tidak dapat dipetakan ulang melalui formulir ini.']);
            }
            if ($mapping) {
                $target = Institution::where('office_id', $office)->whereKey($mapping)->lockForUpdate()->first();
                if (! $target || (! $target->is_active && $mapping !== $locked->mapped_institution_id)) {
                    throw ValidationException::withMessages(['mapped_institution_id' => 'Institusi tujuan tidak aktif atau di luar cakupan. Muat ulang pilihan master.']);
                }
            }
            $before = $locked->only(['status', 'mapped_institution_id']);
            $locked->status = $data['status'];
            $locked->mapped_institution_id = $mapping;
            $locked->revision++;
            $locked->save();
            AuditEntry::create(['actor_id' => $request->user()->id, 'office_id' => $office, 'entity_type' => 'enrollment', 'entity_id' => $locked->id,
                'action' => 'enrollment.updated', 'changes' => ['before' => $before, 'after' => $locked->only(['status', 'mapped_institution_id'])], 'created_at' => now()]);
        }, 3);

        return redirect()->route('participants.show', $enrollment)->with('status', 'Status dan pemetaan tersimpan. Identitas serta isian instansi asli tidak berubah.');
    }
}
