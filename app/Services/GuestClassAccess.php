<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\LearningSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GuestClassAccess
{
    public function session(string $token): LearningSession
    {
        return LearningSession::where('join_token', $token)->where('join_enabled', true)->with(['activity', 'instrument'])->firstOrFail();
    }

    public function secret(Request $request, LearningSession $session): string
    {
        $key = 'guest_classes.'.$session->id;
        if (! $request->session()->has($key)) {
            $request->session()->put($key, Str::random(64));
        }

        return $request->session()->get($key);
    }

    public function hash(string $secret, LearningSession $session): string
    {
        return hash_hmac('sha256', $session->id.'|'.$secret, config('app.key'));
    }

    public function enrollment(Request $request, LearningSession $session): ?Enrollment
    {
        $secret = $request->session()->get('guest_classes.'.$session->id);
        if (! $secret) {
            return null;
        }

        return Enrollment::where('guest_access_hash', $this->hash($secret, $session))->where('joined_session_id', $session->id)->where('status', 'active')->first();
    }
}
