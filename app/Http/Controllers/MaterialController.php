<?php

namespace App\Http\Controllers;

use App\Models\AuditEntry;
use App\Models\LearningSession;
use App\Models\Material;
use App\Models\MaterialVersion;
use App\Services\GuestClassAccess;
use App\Services\MaterialUploadLimit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class MaterialController extends Controller
{
    private function admin(Request $request, LearningSession $session): void
    {
        abort_unless($request->user()->role === 'bi_admin', 403);
        Gate::authorize('update', $session->activity);
    }

    public function index(Request $request, LearningSession $session, MaterialUploadLimit $limit)
    {
        $this->admin($request, $session);

        return response()->view('materials.index', ['session' => $session, 'materials' => Material::where('learning_session_id', $session->id)->with('currentVersion')->orderByDesc('id')->paginate(10), 'maxKb' => $limit->kilobytes()])->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request, Material $material, MaterialUploadLimit $limit)
    {
        $this->admin($request, $material->session);

        return response()->view('materials.show', ['material' => $material, 'versions' => $material->versions()->orderByDesc('version_number')->paginate(10), 'maxKb' => $limit->kilobytes()])->header('Cache-Control', 'private, no-store');
    }

    private function audit(Request $request, Material $material, string $action, array $changes): void
    {
        AuditEntry::create(['actor_id' => $request->user()->id, 'office_id' => $request->user()->office_id, 'entity_type' => 'material', 'entity_id' => $material->id, 'action' => $action, 'changes' => $changes, 'created_at' => now()]);
    }

    public function store(Request $request, LearningSession $session, MaterialUploadLimit $limit)
    {
        $this->admin($request, $session);

        return $this->upload($request, $session, null, $limit);
    }

    public function version(Request $request, Material $material, MaterialUploadLimit $limit)
    {
        $this->admin($request, $material->session);

        return $this->upload($request, $material->session, $material, $limit);
    }

    private function upload(Request $request, LearningSession $session, ?Material $material, MaterialUploadLimit $limit)
    {
        $rules = ['pdf' => ['required', 'file', 'mimes:pdf', 'extensions:pdf', 'max:'.$limit->kilobytes()], 'upload_nonce' => 'required|uuid'];
        if ($material) {
            $rules['revision'] = 'required|integer|min:1';
        } else {
            $rules['title'] = 'required|string|max:255';
            $rules['description'] = 'nullable|string|max:3000';
        }
        $data = $request->validate($rules, ['pdf.required' => 'Pilih berkas PDF.', 'pdf.uploaded' => 'PDF gagal diunggah. Periksa ukuran berkas lalu pilih ulang.', 'pdf.mimes' => 'Berkas harus berupa PDF.', 'pdf.extensions' => 'Gunakan ekstensi .pdf.', 'pdf.max' => 'Ukuran PDF melebihi batas unggahan.']);
        $file = $request->file('pdf');
        $handle = fopen($file->getRealPath(), 'rb');
        $signature = fread($handle, 5);
        fclose($handle);
        if ($signature !== '%PDF-') {
            throw ValidationException::withMessages(['pdf' => 'Isi berkas tidak dikenali sebagai PDF.']);
        }
        $hash = hash_file('sha256', $file->getRealPath());
        $context = $material ? 'version:'.$material->id : 'new:'.$session->id;
        $key = hash_hmac('sha256', $request->user()->id.'|'.$context.'|'.$data['upload_nonce'], config('app.key'));
        // Store once outside transaction retries; remove it if the database/audit fails.
        $path = $file->storeAs('', Str::uuid().'.pdf', 'materials');
        try {
            $result = DB::transaction(function () use ($request, $session, $material, $data, $file, $path, $hash, $key) {
                if ($material) {
                    $locked = Material::whereKey($material->id)->lockForUpdate()->firstOrFail();
                } else {
                    LearningSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
                    $locked = null;
                }
                $existing = MaterialVersion::where('upload_key', $key)->first();
                if ($existing) {
                    if ($existing->sha256 !== $hash) {
                        throw ValidationException::withMessages(['pdf' => 'Permintaan ini sudah tersimpan dengan berkas lain. Muat ulang halaman untuk unggahan baru.']);
                    }

                    return ['material' => $existing->material, 'created' => false];
                }
                if ($locked && $locked->revision !== (int) $data['revision']) {
                    throw ValidationException::withMessages(['revision' => 'Materi telah berubah. Muat ulang halaman sebelum mengunggah.']);
                }
                $target = $locked ?? Material::create(['learning_session_id' => $session->id, 'title' => $data['title'], 'description' => $data['description'] ?? null]);
                $number = ($target->versions()->max('version_number') ?? 0) + 1;
                $original = basename(str_replace('\\', '/', $file->getClientOriginalName()));
                $original = preg_replace('/[\x00-\x1F\x7F]/', '', $original);
                $version = MaterialVersion::create(['material_id' => $target->id, 'version_number' => $number, 'storage_path' => $path, 'original_filename' => mb_substr($original, 0, 255),
                    'size_bytes' => $file->getSize(), 'sha256' => $hash, 'upload_key' => $key, 'uploaded_by' => $request->user()->id, 'created_at' => now()]);
                if ($locked) {
                    $target->revision++;
                    $target->save();
                }
                $this->audit($request, $target, 'material.uploaded', ['after' => ['title' => $target->title, 'version_number' => $number, 'size_bytes' => $version->size_bytes, 'sha256' => $hash]]);

                return ['material' => $target, 'created' => true];
            }, 3);
        } catch (Throwable $exception) {
            $this->cleanup($path);
            throw $exception;
        }
        if (! $result['created']) {
            $this->cleanup($path);
        }

        return redirect()->route('materials.show', $result['material'])->with('status', $result['created'] ? 'PDF tersimpan sebagai versi baru. Terbitkan versi ini agar peserta dapat mengaksesnya.' : 'Unggahan ini sudah tersimpan sebelumnya; tidak dibuat versi ganda.');
    }

    private function cleanup(string $path): void
    {
        try {
            Storage::disk('materials')->delete($path);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function publish(Request $request, Material $material)
    {
        $this->admin($request, $material->session);
        $data = $request->validate(['revision' => 'required|integer|min:1', 'operation' => ['required', Rule::in(['publish', 'hide'])],
            'version_id' => ['required_if:operation,publish', 'nullable', 'integer', Rule::exists('material_versions', 'id')->where('material_id', $material->id)]]);
        DB::transaction(function () use ($request, $material, $data) {
            $locked = Material::whereKey($material->id)->lockForUpdate()->firstOrFail();
            if ($locked->revision !== (int) $data['revision']) {
                throw ValidationException::withMessages(['revision' => 'Materi telah berubah. Muat ulang halaman.']);
            }
            $before = ['visible' => $locked->is_visible, 'version_id' => $locked->current_version_id];
            if ($data['operation'] === 'publish') {
                $version = $locked->versions()->findOrFail($data['version_id']);
                abort_unless(Storage::disk('materials')->exists($version->storage_path), 409, 'Berkas versi ini tidak tersedia. Hubungi pengelola sistem.');
                $locked->current_version_id = $version->id;
                $locked->is_visible = true;
            } else {
                $locked->is_visible = false;
            }
            $locked->revision++;
            $locked->save();
            $this->audit($request, $locked, 'material.'.$data['operation'], ['before' => $before, 'after' => ['visible' => $locked->is_visible, 'version_id' => $locked->current_version_id]]);
        }, 3);

        return redirect()->route('materials.show', $material)->with('status', $data['operation'] === 'publish' ? 'Versi materi diterbitkan untuk peserta kelas.' : 'Materi disembunyikan dari peserta. Riwayat versi tetap tersimpan.');
    }

    private function guest(Request $request, string $token, Material $material, GuestClassAccess $access): MaterialVersion
    {
        $session = $access->session($token);
        abort_unless($material->learning_session_id === $session->id && $access->enrollment($request, $session), 403, 'Isi formulir kelas pada browser ini terlebih dahulu.');
        abort_unless($material->is_visible && $material->current_version_id, 404);
        $version = $material->currentVersion;
        abort_unless($version && $version->material_id === $material->id, 404);
        $requested = $request->query('version');
        if ($requested !== null) {
            abort_unless(is_scalar($requested) && ctype_digit((string) $requested) && (int) $requested === $version->id, 409, 'Versi materi telah berganti. Buka ulang materi dari halaman kelas.');
        }

        return $version;
    }

    public function preview(Request $request, string $token, Material $material, GuestClassAccess $access)
    {
        $version = $this->guest($request, $token, $material, $access);

        return response()->view('materials.preview', compact('material', 'version', 'token'))->header('Cache-Control', 'private, no-store');
    }

    public function guestFile(Request $request, string $token, Material $material, GuestClassAccess $access)
    {
        $version = $this->guest($request, $token, $material, $access);

        return $this->file($material, $version, $request->routeIs('class.material.download'));
    }

    public function adminFile(Request $request, Material $material, MaterialVersion $version)
    {
        $this->admin($request, $material->session);
        abort_unless($version->material_id === $material->id, 404);

        return $this->file($material, $version, true);
    }

    private function file(Material $material, MaterialVersion $version, bool $download)
    {
        $disk = Storage::disk('materials');
        abort_unless($disk->exists($version->storage_path), 404, 'Berkas tidak tersedia.');
        $name = (Str::slug($material->title) ?: 'materi-'.$material->id).'-v'.$version->version_number.'.pdf';
        $headers = ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'no-referrer'];

        return $download ? $disk->download($version->storage_path, $name, $headers) : $disk->response($version->storage_path, $name, $headers, 'inline');
    }
}
