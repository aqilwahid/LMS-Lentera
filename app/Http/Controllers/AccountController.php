<?php

namespace App\Http\Controllers;

use App\Models\AuditEntry;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    private function office(Request $request): int
    {
        abort_unless($request->user()->role === 'bi_admin' && $request->user()->office_id, 403);

        return $request->user()->office_id;
    }

    private function scoped(int $office)
    {
        return User::where(fn ($q) => $q->where(fn ($q) => $q->where('role', 'bi_admin')->where('office_id', $office))
            ->orWhere(fn ($q) => $q->where('role', 'partner')->whereIn('institution_id', Institution::where('office_id', $office)->select('id'))));
    }

    private function audit(Request $request, int $office, User $user, string $action, array $before, array $after): void
    {
        AuditEntry::create(['actor_id' => $request->user()->id, 'office_id' => $office, 'entity_type' => 'user', 'entity_id' => $user->id, 'action' => $action, 'changes' => ['before' => $before, 'after' => $after], 'created_at' => now()]);
    }

    private function sendLink(User $user): string
    {
        try {
            $status = Password::sendResetLink(['email' => $user->email]);
        } catch (\Throwable $e) {
            report($e);

            return 'Email gagal dikirim. Gunakan "Kirim ulang tautan atur kata sandi" nanti.';
        }

        return $status === Password::RESET_LINK_SENT ? 'Tautan atur kata sandi telah dikirim ke email akun.' : 'Tautan belum dikirim (tunggu 60 detik sebelum mencoba lagi).';
    }

    public function index(Request $request)
    {
        $filter = $request->validate(['q' => 'nullable|string|max:100']);
        $accounts = $this->scoped($this->office($request))->with('institution')
            ->when($filter['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%')))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('accounts.index', compact('accounts'));
    }

    public function create(Request $request)
    {
        $office = $this->office($request);

        return view('accounts.form', ['account' => new User, 'institutions' => Institution::where('office_id', $office)->where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $office = $this->office($request);
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => ['required', Rule::in(['bi_admin', 'partner'])],
            'institution_id' => ['nullable', 'required_if:role,partner', Rule::exists('institutions', 'id')->where('office_id', $office)->where('is_active', true)],
        ], ['email.unique' => 'Email sudah dipakai akun lain.', 'institution_id.required_if' => 'Pilih institusi untuk akun mitra.']);
        $isAdmin = $data['role'] === 'bi_admin';
        $user = DB::transaction(function () use ($request, $office, $data, $isAdmin) {
            $user = (new User)->forceFill([
                'name' => $data['name'], 'email' => $data['email'], 'role' => $data['role'],
                'office_id' => $isAdmin ? $office : null, 'institution_id' => $isAdmin ? null : $data['institution_id'],
                'password' => Str::random(40), 'is_active' => true,
            ]);
            $user->save();
            $this->audit($request, $office, $user, 'account.created', [], ['name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'institution_id' => $user->institution_id, 'is_active' => true]);

            return $user;
        });

        return redirect()->route('accounts.edit', $user)->with('status', 'Akun dibuat. '.$this->sendLink($user));
    }

    public function edit(Request $request, int $id)
    {
        $account = $this->scoped($this->office($request))->findOrFail($id);

        return view('accounts.form', ['account' => $account, 'institutions' => collect()]);
    }

    public function update(Request $request, int $id)
    {
        $office = $this->office($request);
        $data = $request->validate(['name' => 'required|string|max:255', 'is_active' => 'required|boolean']);
        DB::transaction(function () use ($request, $office, $id, $data) {
            $account = $this->scoped($office)->lockForUpdate()->findOrFail($id);
            if ($account->id === $request->user()->id && ! $data['is_active']) {
                throw ValidationException::withMessages(['is_active' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
            }
            $before = $account->only(['name', 'is_active']);
            $account->forceFill($data)->save();
            if (! $account->is_active && config('session.driver') === 'database') {
                DB::table('sessions')->where('user_id', $account->id)->delete();
            }
            if ($before !== $account->only(['name', 'is_active'])) {
                $this->audit($request, $office, $account, 'account.updated', $before, $account->only(['name', 'is_active']));
            }
        });

        return redirect()->route('accounts.edit', $id)->with('status', 'Akun tersimpan.');
    }

    public function resetLink(Request $request, int $id)
    {
        $office = $this->office($request);
        $account = $this->scoped($office)->findOrFail($id);
        abort_unless($account->is_active, 422, 'Akun nonaktif.');
        $message = $this->sendLink($account);
        $this->audit($request, $office, $account, 'account.reset_sent', [], []);

        return redirect()->route('accounts.edit', $id)->with('status', $message);
    }
}
