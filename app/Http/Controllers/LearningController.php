<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\L1Response;
use App\Models\LearningSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LearningController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'participant') {
            return view('learning.dashboard', ['enrollments' => $user->enrollments()->where('status', 'active')->with('activity.sessions')->paginate(10)]);
        }
        abort_unless(in_array($user->role, ['bi_admin', 'partner']), 403);
        $query = DB::table('l1_responses as r')->join('enrollments as e', 'e.id', '=', 'r.enrollment_id')->join('activities as a', 'a.id', '=', 'e.activity_id');
        if ($user->role === 'partner') {
            abort_unless($user->institution_id, 403);
            $query->where('e.institution_id', $user->institution_id);
        } else {
            abort_unless($user->office_id, 403);
            $query->where('a.office_id', $user->office_id);
        }
        // Keep sessions, instrument versions and historical institutions separate.
        $aggregates = $query->select('a.title', 'r.learning_session_id', 'r.l1_instrument_id', 'e.institution_name')
            ->selectRaw('COUNT(*) as responses, AVG(r.score_sum) / 5 as mean_score, MIN(r.submitted_at) as period_start, MAX(r.submitted_at) as period_end')
            ->groupBy('a.title', 'r.learning_session_id', 'r.l1_instrument_id', 'e.institution_name')->paginate(15);

        return view('learning.aggregate', compact('aggregates'));
    }

    public function activity(Activity $activity)
    {
        Gate::authorize('view', $activity);

        return view('learning.activity', ['activity' => $activity->load('sessions')]);
    }

    private function enrollment(Request $request, LearningSession $session): Enrollment
    {
        abort_unless($request->user()->role === 'participant', 403);
        Gate::authorize('view', $session->activity);

        return $request->user()->enrollments()->where('activity_id', $session->activity_id)->where('status', 'active')->firstOrFail();
    }

    public function edit(Request $request, LearningSession $session)
    {
        $enrollment = $this->enrollment($request, $session);
        $response = L1Response::where('enrollment_id', $enrollment->id)->where('learning_session_id', $session->id)->first();

        return view('learning.l1', compact('session', 'enrollment', 'response'));
    }

    public function store(Request $request, LearningSession $session)
    {
        $enrollment = $this->enrollment($request, $session);
        DB::transaction(function () use ($request, $session, $enrollment) {
            // Serialize retries; unique constraint is the final safeguard.
            $locked = Enrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'active', 403);
            if (L1Response::where('enrollment_id', $locked->id)->where('learning_session_id', $session->id)->exists()) {
                return;
            }
            $session = LearningSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $instrument = $session->instrument;
            abort_unless($instrument->published_at && count($instrument->questions) === 5, 409, 'Instrumen belum siap.');
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

        return redirect()->route('l1.edit', $session)->with('status', 'Evaluasi sudah dikirim dan tersimpan di server.');
    }
}
