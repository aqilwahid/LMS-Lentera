<?php

namespace App\Actions;

use App\Models\Enrollment;
use App\Models\L2Assessment;
use App\Models\L2Attempt;
use App\Models\LearningSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ParticipateL2
{
    public function handle(Request $request, LearningSession $session, Enrollment $enrollment, string $stage, string $operation): string
    {
        return DB::transaction(function () use ($request, $session, $enrollment, $stage, $operation) {
            $participant = Enrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            abort_unless($participant->status === 'active' && $participant->joined_session_id === $session->id && $participant->activity_id === $session->activity_id, 403);
            $lockedSession = LearningSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedSession->join_enabled && $lockedSession->join_token === $session->join_token, 403, 'Akses QR berubah. Buka tautan kelas yang berlaku.');
            $assessment = L2Assessment::where('learning_session_id', $session->id)->lockForUpdate()->firstOrFail();
            $attempt = L2Attempt::where('l2_assessment_id', $assessment->id)->where('enrollment_id', $participant->id)->where('stage', $stage)->lockForUpdate()->first();
            if ($attempt?->submitted_at || ($operation === 'start' && $attempt)) {
                return $attempt->submitted_at ? 'final' : 'draft'; // Retries preserve the first result, including after closure.
            }
            abort_unless($assessment->isOpen($stage), 403, 'Tes belum dibuka, sudah ditutup, atau dinonaktifkan. Draf tersimpan tetap dipertahankan.');
            if ($operation === 'start') {
                if ($stage === 'post' && $assessment->require_pretest) {
                    abort_unless(L2Attempt::where('l2_assessment_id', $assessment->id)->where('enrollment_id', $participant->id)->where('stage', 'pre')->whereNotNull('submitted_at')->exists(), 403, 'Kirim pretest terlebih dahulu.');
                }
                $instrument = $assessment->instrument;
                abort_unless($instrument->published_at && $instrument->office_id === $session->activity->office_id && $instrument->hasValidQuestions(), 409, 'Instrumen L2 belum siap.');
                L2Attempt::create(['l2_assessment_id' => $assessment->id, 'enrollment_id' => $participant->id, 'l2_instrument_id' => $instrument->id,
                    'stage' => $stage, 'instrument_snapshot' => ['code' => $instrument->code, 'version' => $instrument->version, 'instructions' => $instrument->instructions,
                        'questions' => $instrument->questions, 'scoring' => 'equal_weight_v1', 'require_pretest' => $assessment->require_pretest],
                    'answers' => [], 'started_at' => now(), 'question_count' => count($instrument->questions)]);

                return 'started';
            }
            abort_unless($attempt, 409, 'Mulai tes terlebih dahulu.');
            $request->mergeIfMissing(['answers' => []]);
            $data = $request->validate(['revision' => 'required|integer|min:1', 'answers' => 'present|array|max:'.$attempt->question_count, 'answers.*' => 'required|integer|between:0,3']);
            if ((int) $data['revision'] !== $attempt->revision) {
                throw ValidationException::withMessages(['revision' => 'Jawaban telah berubah pada tab lain. Muat ulang untuk melihat draf terbaru.']);
            }
            $answers = $data['answers'];
            foreach (array_keys($answers) as $index) {
                if (! ctype_digit((string) $index) || (string) (int) $index !== (string) $index || $index >= $attempt->question_count) {
                    throw ValidationException::withMessages(['answers' => 'Nomor soal tidak sesuai versi tes.']);
                }
            }
            $answers = array_map('intval', $answers);
            ksort($answers);
            if ($operation === 'submit' && count($answers) !== $attempt->question_count) {
                throw ValidationException::withMessages(['answers' => 'Jawab seluruh soal sebelum mengirim final. Gunakan Simpan draf jika belum selesai.']);
            }
            $updates = ['answers' => $answers, 'revision' => $attempt->revision + 1];
            if ($operation === 'submit') {
                $correct = 0;
                foreach ($attempt->instrument_snapshot['questions'] as $index => $question) {
                    $correct += $answers[$index] === $question['correct'] ? 1 : 0;
                }
                $updates += ['correct_count' => $correct, 'score_percent' => round($correct * 100 / $attempt->question_count, 2), 'submitted_at' => now()];
            }
            $attempt->update($updates);

            return $operation === 'submit' ? 'final' : 'draft';
        }, 3);
    }
}
