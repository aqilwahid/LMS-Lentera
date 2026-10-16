<?php

namespace App\Queries;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class L2Analytics
{
    public function scope(User $user): Builder
    {
        abort_unless(in_array($user->role, ['bi_admin', 'partner']), 403);
        $officeId = $user->role === 'partner' ? $user->institution?->office_id : $user->office_id;
        abort_unless($officeId && ($user->role !== 'partner' || $user->institution_id), 403);
        $query = DB::table('l2_attempts as t')
            ->join('l2_assessments as x', 'x.id', '=', 't.l2_assessment_id')
            ->join('learning_sessions as s', 's.id', '=', 'x.learning_session_id')
            ->join('activities as a', 'a.id', '=', 's.activity_id')
            ->join('enrollments as e', 'e.id', '=', 't.enrollment_id')
            ->join('l2_instruments as i', 'i.id', '=', 't.l2_instrument_id')
            ->where('a.office_id', $officeId)->whereColumn('i.office_id', 'a.office_id')
            ->whereColumn('e.activity_id', 'a.id')->whereColumn('e.joined_session_id', 's.id')
            ->whereColumn('t.l2_instrument_id', 'x.l2_instrument_id')
            ->whereIn('t.stage', ['pre', 'post'])->whereNotNull('t.submitted_at')
            ->whereBetween('t.score_percent', [0, 100])->where('t.question_count', '>', 0);
        if ($user->role === 'partner') {
            $query->whereRaw('COALESCE(e.institution_id, e.mapped_institution_id) = ?', [$user->institution_id])
                ->where('x.results_visible', true);
        }

        return $query;
    }

    public function withPair(Builder $query): Builder
    {
        return $query->leftJoin('l2_attempts as p', function ($join) {
            $join->on('p.l2_assessment_id', '=', 't.l2_assessment_id')
                ->on('p.enrollment_id', '=', 't.enrollment_id')->on('p.l2_instrument_id', '=', 't.l2_instrument_id')
                ->on('p.question_count', '=', 't.question_count')->where('p.stage', 'pre')
                ->whereNotNull('p.submitted_at')->whereBetween('p.score_percent', [0, 100])
                ->whereColumn('p.submitted_at', '<=', 't.started_at');
        });
    }

    public function measures(Builder $query): Builder
    {
        // Filter t by its own submission date. Its pretest peer may precede the period.
        return $query->selectRaw("COALESCE(SUM(CASE WHEN t.stage = 'pre' THEN 1 ELSE 0 END), 0) as pre_count,
            COALESCE(SUM(CASE WHEN t.stage = 'post' THEN 1 ELSE 0 END), 0) as post_count,
            COALESCE(SUM(CASE WHEN t.stage = 'post' AND p.id IS NOT NULL THEN 1 ELSE 0 END), 0) as pair_count,
            AVG(CASE WHEN t.stage = 'post' AND p.id IS NOT NULL THEN p.score_percent END) as pre_mean,
            AVG(CASE WHEN t.stage = 'post' AND p.id IS NOT NULL THEN t.score_percent END) as post_mean,
            AVG(CASE WHEN t.stage = 'post' AND p.id IS NOT NULL THEN t.score_percent - p.score_percent END) as delta,
            MIN(CASE WHEN t.stage = 'post' AND p.id IS NOT NULL THEN t.submitted_at END) as pair_period_start,
            MAX(CASE WHEN t.stage = 'post' AND p.id IS NOT NULL THEN t.submitted_at END) as pair_period_end");
    }
}
