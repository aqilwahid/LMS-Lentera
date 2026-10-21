<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\L1Instrument;
use App\Models\L2Assessment;
use App\Models\L2Attempt;
use App\Models\L2Instrument;
use App\Models\LearningSession;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class L2AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $partner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['lentera.demo_enabled' => true]);
        $this->seed(DemoSeeder::class);
        $this->admin = User::where('role', 'bi_admin')->firstOrFail();
        $this->partner = User::where('role', 'partner')->firstOrFail();
    }

    private function assessment(?int $office = null, string $name = 'Sesi Analisis Uji', bool $visible = true, ?L2Instrument $instrument = null): L2Assessment
    {
        $office ??= $this->admin->office_id;
        $activity = Activity::create(['office_id' => $office, 'title' => $name.' kegiatan', 'is_demo' => true]);
        $session = LearningSession::create(['activity_id' => $activity->id, 'title' => $name, 'speaker_name' => 'Narasumber uji',
            'l1_instrument_id' => L1Instrument::first()->id, 'starts_at' => '2026-10-16 08:00:00', 'l1_opens_at' => '2026-10-15 00:00:00', 'l1_closes_at' => '2026-10-17 00:00:00']);
        $instrument ??= L2Instrument::create(['office_id' => $office, 'code' => 'L2-'.Str::uuid(), 'name' => $name.' instrumen', 'version' => 1,
            'creation_key' => hash('sha256', Str::uuid()), 'creation_hash' => hash('sha256', 'synthetic'), 'published_at' => now(),
            'questions' => array_fill(0, 5, ['text' => 'SECRET_QUESTION_NOT_FOR_ANALYTICS', 'options' => ['A', 'B', 'C', 'D'], 'correct' => 0])]);

        return L2Assessment::create(['learning_session_id' => $session->id, 'l2_instrument_id' => $instrument->id,
            'pre_opens_at' => '2026-10-15 00:00:00', 'pre_closes_at' => '2026-10-17 23:59:59',
            'post_opens_at' => '2026-10-15 00:00:00', 'post_closes_at' => '2026-10-17 23:59:59', 'enabled' => true, 'results_visible' => $visible]);
    }

    private function enrollment(L2Assessment $assessment, array $extra = []): Enrollment
    {
        return Enrollment::create(['activity_id' => $assessment->session->activity_id, 'joined_session_id' => $assessment->learning_session_id,
            'institution_id' => $this->partner->institution_id, 'institution_name' => $this->partner->institution->name,
            'participant_category' => 'Mahasiswa', 'full_name' => 'SECRET_FULL_NAME', 'identity_number' => 'SECRET_NIM',
            'phone' => 'SECRET_PHONE', 'email' => 'secret_email@example.test', ...$extra]);
    }

    private function attempt(L2Assessment $assessment, Enrollment $enrollment, string $stage, ?int $score, array $extra = []): L2Attempt
    {
        return L2Attempt::create(['l2_assessment_id' => $assessment->id, 'enrollment_id' => $enrollment->id, 'l2_instrument_id' => $assessment->l2_instrument_id,
            'stage' => $stage, 'instrument_snapshot' => ['questions' => $assessment->instrument->questions, 'version' => $assessment->instrument->version],
            'question_count' => 5, 'answers' => [0, 0, 0, 0, 0], 'correct_count' => $score === null ? null : (int) ($score / 20), 'score_percent' => $score,
            'started_at' => $stage === 'pre' ? '2026-10-15 08:00:00' : '2026-10-16 09:00:00',
            'submitted_at' => $score === null ? null : ($stage === 'pre' ? '2026-10-15 09:00:00' : '2026-10-16 10:00:00'), ...$extra]);
    }

    private function pair(L2Assessment $assessment, Enrollment $enrollment, int $pre = 20, int $post = 80): void
    {
        $this->attempt($assessment, $enrollment, 'pre', $pre);
        $this->attempt($assessment, $enrollment, 'post', $post);
    }

    private function url(array $filters = []): string
    {
        return route('analytics.l2', ['from' => '2026-10-16', 'to' => '2026-10-16', ...$filters]);
    }

    public function test_weighted_summary_uses_pairs_not_group_means_and_keeps_source_institutions_separate(): void
    {
        $assessment = $this->assessment();
        $this->pair($assessment, $this->enrollment($assessment), 20, 80);
        $this->pair($assessment, $this->enrollment($assessment), 40, 20);
        $other = Institution::where('id', '!=', $this->partner->institution_id)->firstOrFail();
        $this->pair($assessment, $this->enrollment($assessment, ['institution_id' => $other->id, 'institution_name' => $other->name]), 100, 0);
        $response = $this->actingAs($this->admin)->get($this->url())->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $summary = $response->viewData('summary');
        $this->assertSame(0, (int) $summary->pre_count); // Peer pretests precede the selected period.
        $this->assertSame(3, (int) $summary->post_count);
        $this->assertSame(3, (int) $summary->pair_count);
        $this->assertEqualsWithDelta(53.333333, $summary->pre_mean, 0.00001);
        $this->assertEqualsWithDelta(33.333333, $summary->post_mean, 0.00001);
        $this->assertEqualsWithDelta(-20, $summary->delta, 0.00001);
        $this->assertCount(2, $response->viewData('rows'));
        $response->assertSee('-20,00 poin persentase');
    }

    public function test_csv_export_respects_scope_and_neutralises_formulas(): void
    {
        $released = $this->assessment(name: 'Own released');
        $hidden = $this->assessment(name: 'SECRET_UNRELEASED_SESSION', visible: false);
        $this->pair($released, $this->enrollment($released, ['institution_name' => '=HYPERLINK("x")']), 20, 80);
        $this->pair($hidden, $this->enrollment($hidden));
        $other = Institution::where('id', '!=', $this->partner->institution_id)->firstOrFail();
        $this->pair($released, $this->enrollment($released, ['institution_id' => $other->id, 'institution_name' => 'SECRET_OTHER_INSTITUTION']), 100, 0);
        $response = $this->actingAs($this->partner)->get($this->url(['format' => 'csv']))->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Pasangan sah', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString('20,00;80,00;60,00', $csv);
        foreach (['SECRET_UNRELEASED_SESSION', 'SECRET_OTHER_INSTITUTION', 'SECRET_FULL_NAME', 'secret_email@example.test'] as $private) {
            $this->assertStringNotContainsString($private, $csv);
        }
        $this->assertSame(3, substr_count($this->actingAs($this->admin)->get($this->url(['format' => 'csv']))->streamedContent(), "\n") - 1);
        $this->get($this->url(['format' => 'xlsx']))->assertSessionHasErrors('format');
    }

    public function test_date_boundaries_include_whole_local_day_and_pair_by_post_date(): void
    {
        $assessment = $this->assessment();
        foreach (['2026-10-16 00:00:00', '2026-10-16 23:59:59', '2026-10-17 00:00:00'] as $date) {
            $enrollment = $this->enrollment($assessment);
            $this->attempt($assessment, $enrollment, 'pre', 0);
            $this->attempt($assessment, $enrollment, 'post', 100, ['started_at' => '2026-10-15 23:59:59', 'submitted_at' => $date]);
        }
        $response = $this->actingAs($this->admin)->get($this->url())->assertOk();
        $this->assertSame(2, (int) $response->viewData('summary')->post_count);
        $this->assertSame(2, (int) $response->viewData('summary')->pair_count);
        $this->assertSame(0, (int) $response->viewData('summary')->pre_count);
        $this->assertSame('2026-10-16 23:59:59', $response->viewData('summary')->pair_period_end);
        $preOnly = $this->get($this->url(['from' => '2026-10-15', 'to' => '2026-10-15']))->assertOk();
        $this->assertSame(3, (int) $preOnly->viewData('summary')->pre_count);
        $this->assertSame(0, (int) $preOnly->viewData('summary')->pair_count);
        $preOnly->assertSee('Rata-rata dan selisih belum tersedia');
    }

    public function test_drafts_unpaired_reversed_and_different_question_counts_do_not_become_pairs(): void
    {
        $assessment = $this->assessment();
        $this->pair($assessment, $this->enrollment($assessment));
        $this->attempt($assessment, $this->enrollment($assessment), 'post', 60);
        $this->attempt($assessment, $this->enrollment($assessment), 'pre', 40, ['submitted_at' => '2026-10-16 08:00:00']);
        $this->attempt($assessment, $this->enrollment($assessment), 'post', null);
        $reversed = $this->enrollment($assessment);
        $this->attempt($assessment, $reversed, 'pre', 0, ['submitted_at' => '2026-10-16 12:00:00']);
        $this->attempt($assessment, $reversed, 'post', 100);
        $different = $this->enrollment($assessment);
        $this->attempt($assessment, $different, 'pre', 0, ['question_count' => 4]);
        $this->attempt($assessment, $different, 'post', 100);
        $response = $this->actingAs($this->admin)->get($this->url())->assertOk();
        $this->assertSame(2, (int) $response->viewData('summary')->pre_count);
        $this->assertSame(4, (int) $response->viewData('summary')->post_count);
        $this->assertSame(1, (int) $response->viewData('summary')->pair_count);
        $this->assertEquals(60, $response->viewData('summary')->delta);
        $settings = $this->get(route('l2.settings.edit', $assessment->session))->assertOk();
        $this->assertSame(1, (int) $settings->viewData('pairs')->total);
    }

    public function test_partner_only_gets_released_own_institution_data_and_no_private_payloads(): void
    {
        $released = $this->assessment(name: 'Own released');
        $hidden = $this->assessment(name: 'SECRET_UNRELEASED_SESSION', visible: false);
        $this->pair($released, $this->enrollment($released));
        $this->pair($hidden, $this->enrollment($hidden));
        $other = Institution::where('id', '!=', $this->partner->institution_id)->firstOrFail();
        $this->pair($released, $this->enrollment($released, ['institution_id' => $other->id, 'institution_name' => 'SECRET_OTHER_INSTITUTION']), 100, 0);
        $response = $this->actingAs($this->partner)->get($this->url())->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(1, (int) $response->viewData('summary')->pair_count);
        foreach (['SECRET_UNRELEASED_SESSION', 'SECRET_OTHER_INSTITUTION', 'SECRET_FULL_NAME', 'SECRET_NIM', 'SECRET_PHONE', 'secret_email@example.test', 'SECRET_QUESTION_NOT_FOR_ANALYTICS', 'instrument_snapshot', 'guest_access_hash', 'answers['] as $private) {
            $response->assertDontSee($private);
        }
        $this->getJson($this->url(['session_id' => $hidden->learning_session_id]))->assertJsonValidationErrors('session_id');
        $hidden->update(['results_visible' => true]);
        $this->assertSame(2, (int) $this->get($this->url())->assertOk()->viewData('summary')->pair_count);
        $hidden->update(['results_visible' => false]);
        $this->assertSame(1, (int) $this->get($this->url())->assertOk()->viewData('summary')->pair_count);
        $this->assertSame(3, (int) $this->actingAs($this->admin)->get($this->url())->assertOk()->viewData('summary')->pair_count);
    }

    public function test_office_scope_and_filter_injection_cannot_expose_other_office_even_with_same_institution_id(): void
    {
        $own = $this->assessment();
        $this->pair($own, $this->enrollment($own));
        $office = DB::table('offices')->insertGetId(['name' => 'Office other']);
        $foreign = $this->assessment(office: $office, name: 'SECRET_FOREIGN_OFFICE');
        $this->pair($foreign, $this->enrollment($foreign)); // Deliberately inconsistent institution ownership.
        foreach ([$this->admin, $this->partner] as $user) {
            $response = $this->actingAs($user)->get($this->url())->assertOk()->assertDontSee('SECRET_FOREIGN_OFFICE');
            $this->assertSame(1, (int) $response->viewData('summary')->pair_count);
            $this->getJson($this->url(['office_id' => $office]))->assertJsonValidationErrors('office_id');
            $this->getJson($this->url(['activity_id' => $foreign->session->activity_id]))->assertJsonValidationErrors('activity_id');
            $this->getJson($this->url(['session_id' => $foreign->learning_session_id]))->assertJsonValidationErrors('session_id');
            $this->getJson($this->url(['instrument_id' => $foreign->l2_instrument_id]))->assertJsonValidationErrors('instrument_id');
        }
        $other = Institution::where('id', '!=', $this->partner->institution_id)->first();
        $this->actingAs($this->partner)->getJson($this->url(['institution_id' => $other->id]))->assertJsonValidationErrors('institution_id');
        $this->getJson($this->url(['institution_id' => 0]))->assertJsonValidationErrors('institution_id');
    }

    public function test_audited_mapping_changes_partner_scope_without_rewriting_source_and_revocation_keeps_history(): void
    {
        $assessment = $this->assessment();
        $enrollment = $this->enrollment($assessment, ['institution_id' => null, 'institution_name' => 'Instansi ejaan sumber']);
        $this->pair($assessment, $enrollment);
        $this->assertSame(0, (int) $this->actingAs($this->partner)->get($this->url())->assertOk()->viewData('summary')->pair_count);
        $unmapped = $this->actingAs($this->admin)->get($this->url(['institution_id' => 0]))->assertOk();
        $this->assertSame(1, (int) $unmapped->viewData('summary')->pair_count);
        $this->put(route('participants.update', $enrollment), ['revision' => 1, 'status' => 'active', 'mapped_institution_id' => $this->partner->institution_id])->assertRedirect();
        $response = $this->actingAs($this->partner)->get($this->url())->assertOk()->assertSee('Instansi ejaan sumber');
        $this->assertSame(1, (int) $response->viewData('summary')->pair_count);
        $this->assertSame('Instansi ejaan sumber', $enrollment->fresh()->institution_name);
        $this->assertNull($enrollment->fresh()->institution_id);
        $this->actingAs($this->admin)->put(route('participants.update', $enrollment), ['revision' => 2, 'status' => 'revoked', 'mapped_institution_id' => $this->partner->institution_id])->assertRedirect();
        $this->assertSame(1, (int) $this->actingAs($this->partner)->get($this->url())->assertOk()->viewData('summary')->pair_count);
        $this->assertDatabaseCount('audit_entries', 2);
    }

    public function test_activity_session_version_and_institution_filters_preserve_distinct_groups(): void
    {
        $first = $this->assessment();
        $v2 = $first->instrument->replicate(['id']);
        $v2->forceFill(['version' => 2, 'creation_key' => hash('sha256', Str::uuid())])->save();
        $second = $this->assessment(instrument: $v2); // Identical titles, distinct version IDs.
        $this->pair($first, $this->enrollment($first));
        $this->pair($second, $this->enrollment($second), 100, 0);
        $all = $this->actingAs($this->admin)->get($this->url())->assertOk();
        $this->assertCount(2, $all->viewData('rows'));
        $this->assertSame([1, 2], $all->viewData('rows')->pluck('version')->sort()->values()->all());
        foreach (['activity_id' => $first->session->activity_id, 'session_id' => $first->learning_session_id, 'instrument_id' => $first->l2_instrument_id] as $key => $value) {
            $filtered = $this->get($this->url([$key => $value, 'institution_id' => $this->partner->institution_id]))->assertOk();
            $this->assertCount(1, $filtered->viewData('rows'));
            $this->assertEquals(60, $filtered->viewData('summary')->delta);
        }
        $this->getJson($this->url(['activity_id' => $first->session->activity_id, 'session_id' => $second->learning_session_id]))->assertJsonValidationErrors('session_id');
        $this->getJson($this->url(['instrument_id' => '1 OR 1=1']))->assertJsonValidationErrors('instrument_id');
    }

    public function test_wrong_versions_and_enrollment_session_mismatches_cannot_enter_pairs_or_scope(): void
    {
        $first = $this->assessment();
        $other = $this->assessment();
        $this->pair($first, $this->enrollment($first));
        $mismatch = $this->enrollment($first);
        $this->attempt($first, $mismatch, 'pre', 0, ['l2_instrument_id' => $other->l2_instrument_id]);
        $this->attempt($first, $mismatch, 'post', 100);
        $this->attempt($first, $this->enrollment($other), 'post', 100);
        $response = $this->actingAs($this->admin)->get($this->url())->assertOk();
        $this->assertSame(2, (int) $response->viewData('summary')->post_count);
        $this->assertSame(1, (int) $response->viewData('summary')->pair_count);
        $this->assertEquals(60, $response->viewData('summary')->delta);
    }

    public function test_empty_invalid_date_and_default_year_states_are_explicit(): void
    {
        $response = $this->actingAs($this->admin)->get(route('analytics.l2'))->assertOk()->assertSee('Belum ada hasil final L2');
        $this->assertSame(now()->startOfYear()->toDateString(), $response->viewData('filters')['from']);
        $this->assertNull($response->viewData('summary')->pre_mean);
        $this->getJson($this->url(['from' => '2026-02-30']))->assertJsonValidationErrors('from');
        $this->getJson($this->url(['from' => '2026-10-17', 'to' => '2026-10-16']))->assertJsonValidationErrors('to');
        $this->getJson($this->url(['from' => '']))->assertJsonValidationErrors('from');
    }

    public function test_group_pagination_keeps_filters_and_summary_counts_all_results(): void
    {
        $assessment = $this->assessment();
        for ($i = 0; $i < 16; $i++) {
            $enrollment = $this->enrollment($assessment, ['institution_name' => 'Alias '.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
            $this->attempt($assessment, $enrollment, 'post', 60);
        }
        $response = $this->actingAs($this->partner)->get($this->url(['page' => 2]))->assertOk();
        $this->assertSame(16, $response->viewData('rows')->total());
        $this->assertCount(1, $response->viewData('rows'));
        $this->assertSame(16, (int) $response->viewData('summary')->post_count);
        $this->assertSame(0, (int) $response->viewData('summary')->pair_count);
        $response->assertSee('from=2026-10-16')->assertSee('to=2026-10-16');
    }

    public function test_guests_participant_inactive_and_unscoped_accounts_are_denied(): void
    {
        $this->get($this->url())->assertRedirect('/masuk');
        $this->actingAs(User::where('role', 'participant')->first())->get($this->url())->assertForbidden();
        $this->admin->forceFill(['is_active' => false])->save();
        $this->actingAs($this->admin)->get($this->url())->assertForbidden();
        $this->admin->forceFill(['is_active' => true, 'office_id' => null])->save();
        $this->actingAs($this->admin)->get($this->url())->assertForbidden();
        $this->partner->forceFill(['institution_id' => null])->save();
        $this->actingAs($this->partner)->get($this->url())->assertForbidden();
    }
}
