<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\L1Instrument;
use App\Models\L1Response;
use App\Models\LearningSession;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LearningFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_unique_constraint_rejects_duplicate_final_response(): void
    {
        $this->actingAs($this->participant())->post($this->url(), $this->payload());
        $attributes = L1Response::first()->getAttributes();
        unset($attributes['id']);
        $this->expectException(QueryException::class);
        DB::table('l1_responses')->insert($attributes);
    }

    public function test_enrollment_institution_context_cannot_be_rewritten(): void
    {
        $this->expectException(\LogicException::class);
        Enrollment::first()->update(['institution_name' => 'Institusi pengganti']);
    }

    public function test_session_speaker_cannot_be_rewritten_after_submission(): void
    {
        $this->actingAs($this->participant())->post($this->url(), $this->payload());
        $this->expectException(\LogicException::class);
        LearningSession::first()->update(['speaker_name' => 'Pengganti']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['lentera.demo_enabled' => true]);
        $this->seed(DemoSeeder::class);
    }

    private function participant(): User
    {
        return User::where('role', 'participant')->firstOrFail();
    }

    private function payload(): array
    {
        return ['instrument_id' => L1Instrument::first()->id, 'answers' => [1, 2, 3, 4, 6], 'feedback' => 'Contoh masukan'];
    }

    private function url(): string
    {
        return route('l1.store', LearningSession::first());
    }

    public function test_guest_cannot_access_learning_or_submit(): void
    {
        $this->get('/beranda')->assertRedirect('/masuk');
        $this->post($this->url(), $this->payload())->assertRedirect('/masuk');
        $this->assertDatabaseCount('l1_responses', 0);
    }

    public function test_legacy_records_keep_server_score_and_retry_idempotency(): void
    {
        $this->actingAs($this->participant());
        $this->get('/beranda')->assertOk()->assertSee('Kegiatan saya.');
        $session = LearningSession::first();
        $this->get(route('activities.show', $session->activity))->assertOk()->assertSee($session->title);
        $this->get($this->url())->assertOk()->assertSee('answers[4]', false);
        $this->post($this->url(), [...$this->payload(), 'score_sum' => 30, 'enrollment_id' => 999])->assertRedirect($this->url());
        $this->assertDatabaseHas('l1_responses', ['score_sum' => 16, 'enrollment_id' => Enrollment::first()->id]);
        $original = L1Response::first()->toArray();
        $session->update(['l1_closes_at' => now()->subHour()]);
        $this->post($this->url(), ['answers' => [6, 6, 6, 6, 6]])->assertRedirect($this->url());
        $this->assertDatabaseCount('l1_responses', 1);
        $this->assertSame($original, L1Response::first()->toArray());
        $this->get($this->url())->assertOk()->assertSee('Evaluasi sudah dikirim.')->assertSee('3,20 / 6');
    }

    public function test_invalid_answers_extra_keys_and_stale_instrument_are_rejected(): void
    {
        $this->actingAs($this->participant());
        foreach ([
            ['answers' => [1, 2, 3, 4]],
            ['answers' => [1, 2, 3, 4, 7]],
            ['answers' => [1, 2, 3, 4, 0]],
            ['answers' => [1, 2, 3, 4, 2.5]],
            ['answers' => [1, 2, 3, 4, 5, 6]],
            ['answers' => [1, 2, 3, 4, 5, 'evil' => 6]],
            ['instrument_id' => 999],
            ['feedback' => str_repeat('x', 2001)],
        ] as $invalid) {
            $this->post($this->url(), array_replace($this->payload(), $invalid))->assertSessionHasErrors();
            $this->assertDatabaseCount('l1_responses', 0);
        }
    }

    public function test_non_enrolled_and_revoked_participants_cannot_read_or_write(): void
    {
        $other = User::factory()->create();
        $this->actingAs($other)->get($this->url())->assertForbidden();
        $this->post($this->url(), $this->payload())->assertForbidden();
        Enrollment::first()->update(['status' => 'revoked']);
        $this->actingAs($this->participant())->get($this->url())->assertForbidden();
        $this->post($this->url(), $this->payload())->assertForbidden();
    }

    public function test_partner_and_admin_cannot_submit_as_participant(): void
    {
        foreach (['partner', 'bi_admin'] as $role) {
            $user = User::where('role', $role)->first();
            $this->actingAs($user)->get($this->url())->assertForbidden();
            $this->post($this->url(), $this->payload())->assertForbidden();
        }
        $this->assertDatabaseCount('l1_responses', 0);
    }

    public function test_closed_future_and_unpublished_evaluations_are_rejected(): void
    {
        $this->actingAs($this->participant());
        $session = LearningSession::first();
        $session->update(['l1_closes_at' => now()->subMinute()]);
        $this->post($this->url(), $this->payload())->assertForbidden();
        $session->update(['l1_closes_at' => now()->addDays(2), 'l1_opens_at' => now()->addDay()]);
        $this->post($this->url(), $this->payload())->assertForbidden();
        $draft = L1Instrument::create(['code' => 'DRAFT', 'version' => 1, 'questions' => config('lentera.l1_questions')]);
        $session->update(['l1_instrument_id' => $draft->id, 'l1_opens_at' => now()->subDay()]);
        $this->post($this->url(), $this->payload())->assertStatus(409);
    }

    public function test_historical_institution_snapshot_and_partner_scope(): void
    {
        $participant = $this->participant();
        $this->actingAs($participant)->post($this->url(), $this->payload())->assertRedirect();
        $institution = Institution::where('name', 'UIN Sultan Maulana Hasanuddin Banten')->first();
        $participant->forceFill(['institution_id' => $institution->id])->save();
        $this->assertSame('Universitas Sultan Ageng Tirtayasa', Enrollment::first()->institution_name);
        $partner = User::where('role', 'partner')->first();
        $this->actingAs($partner)->get('/beranda')->assertOk()->assertSee('3,20 / 6')->assertDontSee('Contoh masukan')->assertDontSee($participant->email);
        $csv = $this->get('/beranda?format=csv')->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8')->streamedContent();
        $this->assertStringContainsString(';3,20;', $csv);
        $this->assertStringNotContainsString($participant->email, $csv);
        $partner->forceFill(['institution_id' => $institution->id])->save();
        $this->actingAs($partner)->get('/beranda')->assertOk()->assertSee('Belum ada respons L1')->assertDontSee('3,20 / 6');
        $this->get(route('activities.show', Activity::first()))->assertForbidden();
    }

    public function test_admin_office_scope_is_checked_on_server(): void
    {
        $this->actingAs($this->participant())->post($this->url(), $this->payload());
        $admin = User::where('role', 'bi_admin')->first();
        $this->actingAs($admin)->get('/beranda')->assertOk()->assertSee('3,20 / 6');
        $office = DB::table('offices')->insertGetId(['name' => 'KPwBI lain']);
        $admin->forceFill(['office_id' => $office])->save();
        $this->actingAs($admin)->get('/beranda')->assertOk()->assertDontSee('3,20 / 6');
        $this->get(route('activities.show', Activity::first()))->assertForbidden();
    }

    public function test_dashboard_period_filter_is_inclusive_validated_and_applies_to_csv(): void
    {
        $this->actingAs($this->participant())->post($this->url(), $this->payload());
        $day = now()->toDateString();
        $admin = User::where('role', 'bi_admin')->first();
        $this->actingAs($admin)->get("/beranda?from=$day&to=$day")
            ->assertOk()
            ->assertSee('Ringkasan hasil L1')
            ->assertSee('3,20 / 6');
        $this->get('/beranda?from='.now()->addDay()->toDateString())->assertOk()->assertDontSee('3,20 / 6')->assertSee('Belum ada respons L1');
        $this->get('/beranda?to='.now()->subDay()->toDateString())->assertOk()->assertDontSee('3,20 / 6');
        $this->get("/beranda?from=$day&to=$day&format=csv")->assertOk()->assertDownload('ringkasan-l1-'."$day-sd-$day.csv");
        $this->get("/beranda?from=$day&to=2000-01-01")->assertSessionHasErrors('to');
        $this->get('/beranda?from=bukan-tanggal')->assertSessionHasErrors('from');
    }

    public function test_disabled_accounts_and_login_throttling(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/masuk', ['email' => 'peserta@example.test', 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/masuk', ['email' => 'peserta@example.test', 'password' => 'Lentera-Demo-2026!'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $user = $this->participant();
        $user->forceFill(['is_active' => false])->save();
        $this->actingAs($user)->get('/beranda')->assertForbidden();
    }

    public function test_published_instrument_is_immutable_and_response_keeps_snapshot(): void
    {
        $this->actingAs($this->participant())->post($this->url(), $this->payload());
        $this->assertSame(config('lentera.l1_questions'), L1Response::first()->instrument_snapshot['questions']);
        $this->expectException(\LogicException::class);
        L1Instrument::first()->update(['questions' => ['changed']]);
    }

    public function test_demo_seeding_is_repeatable_and_production_guarded(): void
    {
        $this->seed(DemoSeeder::class);
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('enrollments', 1);
        config(['lentera.demo_enabled' => false]);
        $this->expectException(\RuntimeException::class);
        $this->seed(DemoSeeder::class);
    }

    public function test_logout_invalidates_authentication(): void
    {
        $this->actingAs($this->participant())->post('/keluar')->assertRedirect('/masuk');
        $this->assertGuest();
        $this->get('/beranda')->assertRedirect('/masuk');
    }
}
