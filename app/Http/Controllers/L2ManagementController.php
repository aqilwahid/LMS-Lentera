<?php

namespace App\Http\Controllers;

use App\Models\AuditEntry;
use App\Models\L2Assessment;
use App\Models\L2Attempt;
use App\Models\L2Instrument;
use App\Models\LearningSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class L2ManagementController extends Controller
{
    private function access(Request $request, LearningSession $session): void
    {
        abort_unless($request->user()->role === 'bi_admin' && $request->user()->office_id, 403);
        Gate::authorize('update', $session->activity);
    }

    public function edit(Request $request, LearningSession $session)
    {
        $this->access($request, $session);
        $assessment = L2Assessment::where('learning_session_id', $session->id)->first() ?? new L2Assessment;
        $attempts = L2Attempt::where('l2_assessment_id', $assessment->id);
        $counts = (clone $attempts)->whereNotNull('submitted_at')->selectRaw('stage, COUNT(*) as total')->groupBy('stage')->pluck('total', 'stage');
        $pairs = DB::table('l2_attempts as pre')->join('l2_attempts as post', function ($join) {
            $join->on('pre.l2_assessment_id', '=', 'post.l2_assessment_id')->on('pre.enrollment_id', '=', 'post.enrollment_id')->on('pre.l2_instrument_id', '=', 'post.l2_instrument_id');
        })->where('pre.l2_assessment_id', $assessment->id)->where('pre.stage', 'pre')->where('post.stage', 'post')
            ->whereNotNull('pre.submitted_at')->whereNotNull('post.submitted_at')
            ->selectRaw('COUNT(*) as total, AVG(pre.score_percent) as pre_mean, AVG(post.score_percent) as post_mean, AVG(post.score_percent - pre.score_percent) as delta')->first();

        return response()->view('l2.settings', ['session' => $session, 'assessment' => $assessment,
            'locked' => (clone $attempts)->exists(), 'counts' => $counts, 'pairs' => $pairs,
            'instruments' => L2Instrument::where('office_id', $request->user()->office_id)->whereNotNull('published_at')->orderBy('name')->orderByDesc('version')->get()->filter(fn ($i) => $i->hasValidQuestions()),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, LearningSession $session)
    {
        $this->access($request, $session);
        $data = $request->validate([
            'revision' => 'required|integer|min:0', 'l2_instrument_id' => 'required|integer',
            'pre_opens_at' => 'required|date_format:Y-m-d\\TH:i', 'pre_closes_at' => 'required|date_format:Y-m-d\\TH:i|after:pre_opens_at',
            'post_opens_at' => 'required|date_format:Y-m-d\\TH:i', 'post_closes_at' => 'required|date_format:Y-m-d\\TH:i|after:post_opens_at',
            'enabled' => 'required|boolean', 'require_pretest' => 'required|boolean', 'results_visible' => 'required|boolean',
        ]);
        $instrument = L2Instrument::where('office_id', $request->user()->office_id)->whereNotNull('published_at')->find($data['l2_instrument_id']);
        if (! $instrument || ! $instrument->hasValidQuestions()) {
            throw ValidationException::withMessages(['l2_instrument_id' => 'Pilih instrumen L2 terbit yang valid pada KPwBI Anda.']);
        }
        foreach (['pre_opens_at', 'pre_closes_at', 'post_opens_at', 'post_closes_at'] as $field) {
            $data[$field] = Carbon::createFromFormat('!Y-m-d\\TH:i', $data[$field], config('app.timezone'));
        }
        DB::transaction(function () use ($request, $session, $data) {
            // Same session lock as participant starts: a concurrent start freezes the assignment.
            LearningSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $assessment = L2Assessment::where('learning_session_id', $session->id)->lockForUpdate()->first();
            if ((int) $data['revision'] !== ($assessment?->revision ?? 0)) {
                throw ValidationException::withMessages(['revision' => 'Pengaturan telah berubah. Muat ulang sebelum menyimpan.']);
            }
            $fields = array_diff_key($data, ['revision' => true]);
            $before = $assessment?->only(array_keys($fields)) ?? [];
            if ($assessment && L2Attempt::where('l2_assessment_id', $assessment->id)->exists()
                && ((int) $data['l2_instrument_id'] !== $assessment->l2_instrument_id || (bool) $data['require_pretest'] !== $assessment->require_pretest)) {
                throw ValidationException::withMessages(['l2_instrument_id' => 'Tes sudah dimulai. Versi instrumen dan aturan prasyarat dikunci; buat sesi baru untuk konteks berbeda.']);
            }
            if ($assessment) {
                $assessment->update([...$fields, 'revision' => $assessment->revision + 1]);
            } else {
                $assessment = L2Assessment::create([...$fields, 'learning_session_id' => $session->id]);
            }
            AuditEntry::create(['actor_id' => $request->user()->id, 'office_id' => $request->user()->office_id,
                'entity_type' => 'l2_assessment', 'entity_id' => $assessment->id, 'action' => 'l2.settings.updated',
                'changes' => ['before' => $before, 'after' => $assessment->only(array_keys($fields))], 'created_at' => now()]);
        }, 3);

        return redirect()->route('l2.settings.edit', $session)->with('status', 'Pengaturan L2 tersimpan. Peserta tetap mengikuti jadwal yang ditetapkan.');
    }
}
