<?php

namespace App\Http\Controllers;

use App\Actions\SubmitL1;
use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\L1Response;
use App\Models\LearningSession;
use Carbon\CarbonImmutable;
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
            $query->whereRaw('COALESCE(e.institution_id, e.mapped_institution_id) = ?', [$user->institution_id]);
        } else {
            abort_unless($user->office_id, 403);
            $query->where('a.office_id', $user->office_id);
        }
        $filters = $request->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from']);
        $zone = config('app.timezone');
        if ($filters['from'] ?? null) {
            $query->where('r.submitted_at', '>=', CarbonImmutable::createFromFormat('!Y-m-d', $filters['from'], $zone)->startOfDay());
        }
        if ($filters['to'] ?? null) {
            $query->where('r.submitted_at', '<=', CarbonImmutable::createFromFormat('!Y-m-d', $filters['to'], $zone)->endOfDay());
        }
        // Keep sessions, instrument versions and historical institutions separate.
        $totals = (clone $query)->selectRaw('COUNT(*) as responses, AVG(r.score_sum) / 5 as mean_score, COUNT(DISTINCT r.learning_session_id) as sessions, COUNT(DISTINCT e.institution_name) as institutions')->first();
        $aggregates = $query->select('a.title', 'r.learning_session_id', 'r.l1_instrument_id', 'e.institution_name')
            ->selectRaw('COUNT(*) as responses, AVG(r.score_sum) / 5 as mean_score, MIN(r.submitted_at) as period_start, MAX(r.submitted_at) as period_end')
            ->groupBy('a.title', 'r.learning_session_id', 'r.l1_instrument_id', 'e.institution_name')->orderBy('a.title')->orderBy('r.learning_session_id')->orderBy('e.institution_name');
        if ($request->query('format') === 'csv') {
            return $this->csv('ringkasan-l1-'.($filters['from'] ?? 'awal').'-sd-'.($filters['to'] ?? now()->toDateString()).'.csv', ['Kegiatan', 'Sesi (nomor)', 'Instrumen (nomor)', 'Isian institusi', 'Jumlah respons', 'Rata-rata (1-6)', 'Respons pertama', 'Respons terakhir'],
                $aggregates->lazy()->map(fn ($r) => [$r->title, $r->learning_session_id, $r->l1_instrument_id, $r->institution_name, $r->responses, (float) $r->mean_score, $r->period_start, $r->period_end]));
        }
        $aggregates = $aggregates->paginate(15)->withQueryString();

        $activities = $user->role === 'bi_admin'
            ? Activity::where('office_id', $user->office_id)->with('sessions')->paginate(10, ['*'], 'activities_page')
            : null;

        return view('learning.aggregate', compact('aggregates', 'activities', 'totals'));
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

    public function store(Request $request, LearningSession $session, SubmitL1 $action)
    {
        $enrollment = $this->enrollment($request, $session);
        $action->handle($request, $enrollment, $session);

        return redirect()->route('l1.edit', $session)->with('status', 'Evaluasi sudah dikirim dan tersimpan di server.');
    }
}
