<?php

namespace App\Http\Controllers;

use App\Actions\ParticipateL2;
use App\Models\L2Assessment;
use App\Models\L2Attempt;
use App\Models\L2Instrument;
use App\Services\GuestClassAccess;
use Illuminate\Http\Request;

class GuestL2Controller extends Controller
{
    public function __construct(private GuestClassAccess $access) {}

    public function show(Request $request, string $token, string $stage)
    {
        $session = $this->access->session($token);
        $enrollment = $this->access->enrollment($request, $session);
        if (! $enrollment) {
            return redirect()->route('class.join', $token);
        }
        $assessment = L2Assessment::where('learning_session_id', $session->id)->firstOrFail();
        $attempts = L2Attempt::where('l2_assessment_id', $assessment->id)->where('enrollment_id', $enrollment->id)->get()->keyBy('stage');
        $attempt = $attempts->get($stage);
        // Explicit projection: never render the snapshot answer key or hidden scores to participants.
        $questions = $attempt ? L2Instrument::publicQuestions($attempt->instrument_snapshot['questions']) : [];
        $result = $attempt?->submitted_at && $assessment->results_visible ? $attempt->only(['correct_count', 'question_count', 'score_percent']) : null;
        $pre = $attempts->get('pre');
        $post = $attempts->get('post');
        $delta = $assessment->results_visible && $pre?->submitted_at && $post?->submitted_at
            && $pre->l2_instrument_id === $post->l2_instrument_id && $pre->question_count === $post->question_count
            && $pre->score_percent !== null && $post->score_percent !== null
            && $pre->submitted_at->lessThanOrEqualTo($post->started_at)
            ? (float) $post->score_percent - (float) $pre->score_percent : null;
        $state = $attempt ? ['revision' => $attempt->revision, 'answers' => $attempt->answers, 'submitted_at' => $attempt->submitted_at,
            'instructions' => $attempt->instrument_snapshot['instructions'], 'code' => $attempt->instrument_snapshot['code'], 'version' => $attempt->instrument_snapshot['version']] : null;

        return response()->view('l2.exam', ['session' => $session, 'stage' => $stage, 'assessment' => $assessment,
            'state' => $state, 'questions' => $questions, 'result' => $result, 'delta' => $delta,
            'canStart' => $assessment->isOpen($stage) && ($stage === 'pre' || ! $assessment->require_pretest || $attempts->get('pre')?->submitted_at),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function mutate(Request $request, string $token, string $stage, string $operation, ParticipateL2 $action)
    {
        $session = $this->access->session($token);
        $enrollment = $this->access->enrollment($request, $session);
        abort_unless($enrollment, 403, 'Bergabunglah ke kelas melalui browser ini terlebih dahulu.');
        $outcome = $action->handle($request, $session, $enrollment, $stage, $operation);

        return redirect()->route('class.l2.show', [$token, $stage])->with('status', match ($outcome) {
            'started' => 'Tes dibuka. Jawaban disimpan setelah Anda menekan Simpan draf atau Kirim final.',
            'draft' => 'Draf jawaban tersimpan di server.',
            default => 'Jawaban final sudah dikirim dan tersimpan di server.',
        });
    }
}
