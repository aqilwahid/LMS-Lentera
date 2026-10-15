<?php

namespace App\Http\Controllers;

use App\Models\AuditEntry;
use App\Models\Institution;
use App\Models\Speaker;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MasterController extends Controller
{
    private function model(Request $request, string $kind): string
    {
        abort_unless($request->user()->role === 'bi_admin' && $request->user()->office_id, 403);

        return match ($kind) {
            'institusi' => Institution::class, 'narasumber' => Speaker::class, default => abort(404)
        };
    }

    private function record(Request $request, string $kind, int $id)
    {
        $model = $this->model($request, $kind);

        return $model::where('office_id', $request->user()->office_id)->findOrFail($id);
    }

    public function index(Request $request, string $kind)
    {
        $model = $this->model($request, $kind);
        $filter = $request->validate(['q' => 'nullable|string|max:100']);
        $records = $model::where('office_id', $request->user()->office_id)
            ->when($filter['q'] ?? null, fn ($q, $term) => $q->where('name', 'like', '%'.$term.'%'))->orderBy('name')->paginate(15)->withQueryString();

        return view('masters.index', compact('kind', 'records'));
    }

    public function create(Request $request, string $kind)
    {
        $model = $this->model($request, $kind);

        return view('masters.form', ['kind' => $kind, 'record' => new $model]);
    }

    public function edit(Request $request, string $kind, int $id)
    {
        return view('masters.form', ['kind' => $kind, 'record' => $this->record($request, $kind, $id)]);
    }

    private function data(Request $request, string $kind, $record = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', Rule::unique($kind === 'institusi' ? 'institutions' : 'speakers', 'name')->where('office_id', $request->user()->office_id)->ignore($record?->id)],
            'is_active' => 'required|boolean',
        ];
        if ($record) {
            $rules['revision'] = 'required|integer|min:1';
        }
        if ($kind === 'narasumber') {
            $rules['organization'] = 'nullable|string|max:255';
            $rules['type'] = ['required', Rule::in(['internal', 'external'])];
        }

        return $request->validate($rules, ['name.unique' => 'Nama sudah tersedia pada KPwBI Anda. Gunakan master yang sudah ada.', 'name.required' => 'Nama wajib diisi.']);
    }

    private function writeAudit(Request $request, string $kind, $record, string $action, array $before, array $after): void
    {
        AuditEntry::create(['actor_id' => $request->user()->id, 'office_id' => $request->user()->office_id, 'entity_type' => $kind === 'institusi' ? 'institution' : 'speaker', 'entity_id' => $record->id, 'action' => $action, 'changes' => ['before' => $before, 'after' => $after], 'created_at' => now()]);
    }

    public function store(Request $request, string $kind)
    {
        $model = $this->model($request, $kind);
        $data = $this->data($request, $kind);
        try {
            $record = DB::transaction(function () use ($request, $model, $kind, $data) {
                $record = $model::create([...$data, 'office_id' => $request->user()->office_id]);
                $this->writeAudit($request, $kind, $record, 'master.created', [], $record->only(array_keys($data)));

                return $record;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => 'Nama sudah tersedia pada KPwBI Anda.']);
        }

        return redirect()->route('masters.edit', [$kind, $record->id])->with('status', 'Master tersimpan.');
    }

    public function update(Request $request, string $kind, int $id)
    {
        $record = $this->record($request, $kind, $id);
        $data = $this->data($request, $kind, $record);
        try {
            DB::transaction(function () use ($request, $kind, $record, $data) {
                $locked = $record->newQuery()->whereKey($record->id)->lockForUpdate()->firstOrFail();
                if ((int) $data['revision'] !== $locked->revision) {
                    throw ValidationException::withMessages(['revision' => 'Data telah berubah. Muat ulang halaman sebelum menyimpan.']);
                }
                unset($data['revision']);
                $before = $locked->only(array_keys($data));
                $locked->fill($data);
                $locked->revision++;
                $locked->save();
                $this->writeAudit($request, $kind, $locked, 'master.updated', $before, $locked->only(array_keys($data)));
            }, 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => 'Nama sudah tersedia pada KPwBI Anda.']);
        }

        return redirect()->route('masters.edit', [$kind, $id])->with('status', 'Perubahan master tersimpan. Riwayat kepesertaan dan nama pada sesi lama tetap dipertahankan.');
    }
}
