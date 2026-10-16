<?php

namespace App\Actions;

use App\Models\Enrollment;
use App\Models\L1Response;
use App\Models\LearningSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubmitL1
{
    public function handle(Request $request, Enrollment $enrollment, LearningSession $session): void
    {
        DB::transaction(function () use ($request, $enrollment, $session) {
            $locked = Enrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'active' && $locked->activity_id === $session->activity_id, 403);
            if ($locked->joined_session_id) {
                abort_unless($locked->joined_session_id === $session->id, 403);
            }
            if (L1Response::where('enrollment_id', $locked->id)->where('learning_session_id', $session->id)->exists()) {
                return;
            }
            $session = LearningSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $instrument = $session->instrument;
            abort_unless($instrument->published_at && $instrument->hasValidQuestions(), 409, 'Instrumen belum siap.');
            abort_unless(now()->betweenIncluded($session->l1_opens_at, $session->l1_closes_at), 403, 'Evaluasi belum dibuka atau sudah ditutup.');
            $data = $request->validate([
                'instrument_id' => ['required', 'integer', 'in:'.$instrument->id],
                'answers' => 'required|array:0,1,2,3,4|size:5', 'answers.*' => 'required|integer|between:1,6',
                'feedback' => 'nullable|string|max:2000',
            ]);
            $answers = array_map('intval', $data['answers']);
            L1Response::create([
                'enrollment_id' => $locked->id, 'learning_session_id' => $session->id, 'l1_instrument_id' => $instrument->id,
                'instrument_snapshot' => ['code' => $instrument->code, 'version' => $instrument->version, 'questions' => $instrument->questions, 'scale' => [1, 2, 3, 4, 5, 6]],
                'speaker_name' => $session->speaker_name, 'answers' => $answers, 'score_sum' => array_sum($answers),
                'feedback' => $data['feedback'] ?? null, 'submitted_at' => now(),
            ]);
        }, 3);
    }
}
