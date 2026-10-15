<?php

namespace App\Http\Controllers;

use App\Models\LearningSession;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClassAccessController extends Controller
{
    private function authorizeSession(Request $request, LearningSession $session): void
    {
        abort_unless($request->user()->role === 'bi_admin', 403);
        Gate::authorize('view', $session->activity);
        abort_unless($session->join_token && $session->join_enabled, 404);
    }

    public function show(Request $request, LearningSession $session)
    {
        $this->authorizeSession($request, $session);

        return view('classes.access', ['session' => $session, 'joinUrl' => route('class.join', $session->join_token)]);
    }

    public function qr(Request $request, LearningSession $session)
    {
        $this->authorizeSession($request, $session);
        $result = (new SvgWriter)->write(new QrCode(data: route('class.join', $session->join_token), size: 320, margin: 16));

        return response($result->getString())->header('Content-Type', 'image/svg+xml')->header('Cache-Control', 'private, no-store');
    }
}
