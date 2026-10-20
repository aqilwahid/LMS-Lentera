<?php

namespace App\Http\Controllers;

use App\Models\AuditEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    private const RULES = ['password' => ['required', 'confirmed', 'string', 'min:12', 'max:255', 'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/']];

    private const MESSAGES = [
        'password.min' => 'Kata sandi minimal 12 karakter.',
        'password.regex' => 'Kata sandi harus memuat huruf dan angka.',
        'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        'password.required' => 'Kata sandi wajib diisi.',
    ];

    // Only active manager/partner accounts may recover; participants have no account.
    private function credentials(string $email): array
    {
        return ['email' => strtolower(trim($email)), 'is_active' => true, 'role' => ['bi_admin', 'partner']];
    }

    public function forgot()
    {
        return view('auth.forgot');
    }

    public function sendLink(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        try {
            Password::sendResetLink($this->credentials($data['email']));
        } catch (\Throwable $e) {
            report($e);
        }

        // Same answer for every email so accounts cannot be enumerated.
        return back()->with('status', 'Jika email terdaftar sebagai akun pengelola/mitra aktif, tautan pemulihan telah dikirim. Tautan berlaku 60 menit.');
    }

    public function reset(Request $request, string $token)
    {
        return view('auth.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['token' => 'required|string', 'email' => 'required|email|max:255', 'password_confirmation' => 'required|string', ...self::RULES], self::MESSAGES);
        $status = Password::reset(
            [...$this->credentials($data['email']), 'password' => $data['password'], 'password_confirmation' => $request->input('password_confirmation'), 'token' => $data['token']],
            function ($user) use ($data) {
                DB::transaction(function () use ($user, $data) {
                    $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
                    if (config('session.driver') === 'database') {
                        DB::table('sessions')->where('user_id', $user->id)->delete();
                    }
                    AuditEntry::create(['actor_id' => $user->id, 'office_id' => $user->office_id ?? $user->institution->office_id, 'entity_type' => 'user', 'entity_id' => $user->id, 'action' => 'account.password_reset', 'changes' => ['before' => [], 'after' => []], 'created_at' => now()]);
                });
            }
        );
        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Tautan pemulihan tidak valid atau sudah kedaluwarsa. Minta tautan baru.']);
        }
        Auth::logout();

        return redirect()->route('login')->with('status', 'Kata sandi diperbarui. Silakan masuk.');
    }
}
