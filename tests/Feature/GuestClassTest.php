<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\LearningSession;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuestClassTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['lentera.demo_enabled' => true]);
        $this->seed(DemoSeeder::class);
    }

    private function classSession(): LearningSession
    {
        return LearningSession::firstOrFail();
    }

    private function joinUrl(): string
    {
        return route('class.join', $this->classSession()->join_token);
    }

    private function payload(): array
    {
        return ['full_name' => 'Peserta QR Contoh', 'institution_name' => 'Universitas Sultan Ageng Tirtayasa', 'identity_number' => '0012345', 'department' => 'Ilmu Ekonomi', 'faculty' => 'Ekonomi dan Bisnis', 'phone' => '+62 812-3456-7890', 'participant_category' => 'Mahasiswa', 'email' => 'contoh-qr@example.test', 'speaker_name' => 'Nama palsu'];
    }

    private function register(): void
    {
        $this->get($this->joinUrl())->assertOk();
        $this->post($this->joinUrl(), $this->payload())->assertRedirect();
    }

    public function test_qr_join_and_l1_work_without_account_and_store_all_fields(): void
    {
        $this->register();
        $this->assertGuest();
        $this->assertDatabaseCount('users', 3);
        $enrollment = Enrollment::whereNotNull('guest_access_hash')->firstOrFail();
        foreach (array_diff_key($this->payload(), ['speaker_name' => true]) as $key => $value) {
            $this->assertSame($value, $enrollment->$key);
        }
        $this->assertNull($enrollment->user_id);
        $this->assertNotNull($enrollment->institution_id);
        $session = $this->classSession();
        $url = route('class.l1.store', $session->join_token);
        $this->get(route('class.show', $session->join_token))->assertOk()->assertSee('Peserta QR Contoh')->assertSee('Buka evaluasi L1');
        $this->get($url)->assertOk()->assertSee('answers[4]', false);
        $this->post($url, ['instrument_id' => $session->l1_instrument_id, 'answers' => [1, 2, 3, 4, 6], 'score_sum' => 30, 'speaker_name' => 'Palsu'])->assertRedirect($url);
        $this->assertDatabaseHas('l1_responses', ['enrollment_id' => $enrollment->id, 'score_sum' => 16, 'speaker_name' => $session->speaker_name]);
        $this->get($url)->assertOk()->assertSee('Evaluasi sudah dikirim.')->assertSee('3,20 / 6');
        $this->post($url, ['answers' => [6, 6, 6, 6, 6]])->assertRedirect($url);
        $this->assertDatabaseCount('l1_responses', 1);
    }

    public function test_registration_retry_does_not_duplicate_or_overwrite_identity(): void
    {
        $this->register();
        $this->post($this->joinUrl(), array_replace($this->payload(), ['full_name' => 'Pengganti']))->assertRedirect();
        $this->assertDatabaseCount('enrollments', 2);
        $this->assertSame('Peserta QR Contoh', Enrollment::whereNotNull('guest_access_hash')->first()->full_name);
    }

    public function test_required_identity_fields_and_phone_are_validated(): void
    {
        $this->get($this->joinUrl());
        $this->post($this->joinUrl(), [])->assertSessionHasErrors(['full_name', 'institution_name', 'identity_number', 'department', 'faculty', 'phone', 'participant_category', 'email']);
        $this->post($this->joinUrl(), array_replace($this->payload(), ['email' => 'wrong', 'phone' => 'abc']))->assertSessionHasErrors(['email', 'phone']);
        $this->assertDatabaseCount('enrollments', 1);
    }

    public function test_unknown_institution_is_retained_without_guessing_master(): void
    {
        $this->get($this->joinUrl());
        $this->post($this->joinUrl(), array_replace($this->payload(), ['institution_name' => 'Instansi Eksternal Baru']))->assertRedirect();
        $enrollment = Enrollment::whereNotNull('guest_access_hash')->first();
        $this->assertNull($enrollment->institution_id);
        $this->assertSame('Instansi Eksternal Baru', $enrollment->institution_name);
    }

    public function test_new_browser_cannot_read_or_submit_someone_elses_response_even_with_same_email(): void
    {
        $this->register();
        $url = route('class.l1.store', $this->classSession()->join_token);
        $this->post($url, ['instrument_id' => $this->classSession()->l1_instrument_id, 'answers' => [5, 5, 5, 5, 5]]);
        $this->app['session']->flush();
        $this->get($url)->assertRedirect($this->joinUrl());
        $this->post($url, ['enrollment_id' => Enrollment::whereNotNull('guest_access_hash')->first()->id, 'email' => $this->payload()['email']])->assertForbidden();
        $this->assertDatabaseCount('l1_responses', 1);
    }

    public function test_disabled_unknown_and_closed_join_links(): void
    {
        $this->get(route('class.join', 'invalid'))->assertNotFound();
        $session = $this->classSession();
        $session->update(['join_enabled' => false]);
        $this->get($this->joinUrl())->assertNotFound();
        $session->update(['join_enabled' => true, 'join_closes_at' => now()->subHour()]);
        $this->get($this->joinUrl())->assertOk()->assertSee('Pendaftaran belum dibuka atau sudah ditutup.');
        $this->post($this->joinUrl(), $this->payload())->assertForbidden();
        $session->update(['join_closes_at' => now()->addDays(2), 'join_opens_at' => now()->addDay()]);
        $this->post($this->joinUrl(), $this->payload())->assertForbidden();
    }

    public function test_revoked_guest_and_other_class_are_not_accessible(): void
    {
        $this->register();
        $session = $this->classSession();
        $other = LearningSession::create(['activity_id' => $session->activity_id, 'l1_instrument_id' => $session->l1_instrument_id, 'title' => 'Sesi lain', 'speaker_name' => 'Narasumber lain', 'starts_at' => now(), 'l1_opens_at' => now()->subDay(), 'l1_closes_at' => now()->addDay(), 'join_token' => Str::random(48), 'join_enabled' => true, 'join_opens_at' => now()->subDay(), 'join_closes_at' => now()->addDay()]);
        $this->get(route('class.l1.edit', $other->join_token))->assertRedirect(route('class.join', $other->join_token));
        $this->post(route('class.l1.store', $other->join_token), [])->assertForbidden();
        Enrollment::whereNotNull('guest_access_hash')->first()->update(['status' => 'revoked']);
        $this->post(route('class.l1.store', $session->join_token), [])->assertForbidden();
        $this->post($this->joinUrl(), $this->payload())->assertForbidden();
    }

    public function test_qr_is_generated_only_for_bi_admin_in_own_office(): void
    {
        $session = $this->classSession();
        $this->get(route('class.access', $session))->assertRedirect('/masuk');
        $this->actingAs(User::where('role', 'partner')->first())->get(route('class.qr', $session))->assertForbidden();
        $admin = User::where('role', 'bi_admin')->first();
        $this->actingAs($admin)->get(route('class.access', $session))->assertOk()->assertSee($session->join_token);
        $this->get(route('class.qr', $session))->assertOk()->assertHeader('Content-Type', 'image/svg+xml')->assertSee('<svg', false);
        $office = DB::table('offices')->insertGetId(['name' => 'KPwBI lain']);
        $admin->forceFill(['office_id' => $office])->save();
        $this->actingAs($admin)->get(route('class.qr', $session))->assertForbidden();
    }

    public function test_participant_accounts_can_no_longer_login_but_staff_can(): void
    {
        $this->post('/masuk', ['email' => 'peserta@example.test', 'password' => 'Lentera-Demo-2026!'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/masuk', ['email' => 'pengelola@example.test', 'password' => 'Lentera-Demo-2026!'])->assertRedirect('/beranda');
        $this->assertAuthenticatedAs(User::where('role', 'bi_admin')->first());
    }

    public function test_join_rate_limit_is_per_browser_not_shared_campus_ip(): void
    {
        $this->get($this->joinUrl());
        $this->withCookie(config('session.cookie'), $this->app['session']->getId());
        for ($i = 0; $i < 30; $i++) {
            $this->post($this->joinUrl(), [])->assertSessionHasErrors();
        }
        $this->post($this->joinUrl(), [])->assertStatus(429);
        $this->app['session']->invalidate();
        $this->withCookie(config('session.cookie'), $this->app['session']->getId());
        $this->get($this->joinUrl());
        $this->post($this->joinUrl(), [])->assertSessionHasErrors()->assertStatus(302);
    }
}
