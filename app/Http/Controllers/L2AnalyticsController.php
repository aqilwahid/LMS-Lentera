<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Queries\L2Analytics;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class L2AnalyticsController extends Controller
{
    public function index(Request $request, L2Analytics $analytics)
    {
        $user = $request->user();
        $base = $analytics->scope($user);
        // Options use eligible final results across all periods, within the same server scope.
        $activities = (clone $base)->select('a.id', 'a.title')->distinct()->orderBy('a.title')->get();
        $sessions = (clone $base)->select('s.id', 's.title', 's.activity_id')->distinct()->orderBy('s.title')->get();
        $instruments = (clone $base)->select('i.id', 'i.name', 'i.version')->distinct()->orderBy('i.name')->orderByDesc('i.version')->get();
        $institutions = Institution::where('office_id', $user->role === 'bi_admin' ? $user->office_id : $user->institution->office_id)
            ->when($user->role === 'partner', fn ($q) => $q->whereKey($user->institution_id))->orderBy('name')->get(['id', 'name', 'is_active']);
        $request->mergeIfMissing(['from' => now()->startOfYear()->toDateString(), 'to' => now()->endOfYear()->toDateString()]);
        $filters = $request->validate([
            'from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from',
            'activity_id' => ['nullable', 'integer', Rule::in($activities->pluck('id')->all())],
            'session_id' => ['nullable', 'integer', Rule::in($sessions->pluck('id')->all())],
            'instrument_id' => ['nullable', 'integer', Rule::in($instruments->pluck('id')->all())],
            'institution_id' => ['nullable', 'integer', Rule::in($user->role === 'bi_admin' ? [0, ...$institutions->pluck('id')->all()] : [$user->institution_id])],
            'office_id' => 'prohibited', 'format' => 'nullable|in:csv',
        ], ['in' => 'Pilihan tidak tersedia pada cakupan akun Anda.', 'office_id.prohibited' => 'Cakupan KPwBI ditentukan dari akun.']);
        if (! empty($filters['session_id']) && ! empty($filters['activity_id'])
            && $sessions->firstWhere('id', (int) $filters['session_id'])->activity_id !== (int) $filters['activity_id']) {
            throw ValidationException::withMessages(['session_id' => 'Sesi tidak termasuk kegiatan yang dipilih.']);
        }
        $query = $analytics->withPair(clone $base)->whereBetween('t.submitted_at', [
            CarbonImmutable::createFromFormat('!Y-m-d', $filters['from'], config('app.timezone'))->startOfDay(),
            CarbonImmutable::createFromFormat('!Y-m-d', $filters['to'], config('app.timezone'))->endOfDay(),
        ]);
        foreach (['activity_id' => 'a.id', 'session_id' => 's.id', 'instrument_id' => 'i.id'] as $filter => $column) {
            if (! empty($filters[$filter])) {
                $query->where($column, (int) $filters[$filter]);
            }
        }
        if (isset($filters['institution_id'])) {
            if ((int) $filters['institution_id'] === 0) {
                $query->whereNull('e.institution_id')->whereNull('e.mapped_institution_id');
            } else {
                $query->whereRaw('COALESCE(e.institution_id, e.mapped_institution_id) = ?', [(int) $filters['institution_id']]);
            }
        }
        $summary = $analytics->measures(clone $query)->first();
        $grouped = (clone $query)->leftJoin('institutions as n', function ($join) {
            $join->on('n.id', '=', DB::raw('COALESCE(e.institution_id, e.mapped_institution_id)'))->on('n.office_id', '=', 'a.office_id');
        })->select('a.id as activity_id', 'a.title as activity_title', 's.id as session_id', 's.title as session_title',
            'x.id as assessment_id', 'i.id as instrument_id', 'i.name as instrument_name', 'i.version',
            DB::raw('COALESCE(e.institution_id, e.mapped_institution_id) as institution_id'),
            'e.institution_name as institution_source_name', 'n.name as institution_master_name')
            ->groupBy('a.id', 'a.title', 's.id', 's.title', 'x.id', 'i.id', 'i.name', 'i.version',
                DB::raw('COALESCE(e.institution_id, e.mapped_institution_id)'), 'e.institution_name', 'n.name');
        $ordered = $analytics->measures($grouped)->orderByDesc('s.id')->orderBy('e.institution_name')->orderBy('institution_id');
        if (($filters['format'] ?? null) === 'csv') {
            $num = fn ($v) => $v === null ? null : (float) $v;

            return $this->csv('analisis-l2-'.$filters['from'].'-sd-'.$filters['to'].'.csv',
                ['Kegiatan', 'Sesi', 'Isian institusi', 'Master institusi', 'Instrumen', 'Versi', 'Pretest final', 'Post-test final', 'Pasangan sah', 'Rata-rata pretest', 'Rata-rata post-test', 'Selisih post-pre'],
                $ordered->lazy()->map(fn ($r) => [$r->activity_title, $r->session_title, $r->institution_source_name, $r->institution_master_name ?? 'Belum dikenali atau dipetakan', $r->instrument_name, $r->version, $r->pre_count, $r->post_count, $r->pair_count, $num($r->pre_mean), $num($r->post_mean), $num($r->delta)]));
        }
        $rows = $ordered->paginate(15)->withQueryString();

        return response()->view('analytics.l2', compact('filters', 'activities', 'sessions', 'instruments', 'institutions', 'summary', 'rows'))->header('Cache-Control', 'private, no-store');
    }
}
