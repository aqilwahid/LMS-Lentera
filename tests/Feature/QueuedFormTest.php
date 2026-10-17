<?php

namespace Tests\Feature;

use App\Models\LearningSession;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueuedFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['lentera.demo_enabled' => true]);
        $this->seed(DemoSeeder::class);
    }

    public function test_async_registration_and_l1_confirm_once_and_keep_flash_for_following_navigation(): void
    {
        $session = LearningSession::firstOrFail();
        $join = route('class.join', $session->join_token);
        $this->get($join)->assertOk();
        $payload = ['full_name' => 'Queue Synthetic', 'institution_name' => 'Demo institution', 'identity_number' => '00009',
            'department' => 'Demo', 'faculty' => 'Demo', 'phone' => '08123456789', 'participant_category' => 'Mahasiswa', 'email' => 'queue@example.test'];
        $headers = ['X-Lentera-Async' => '1'];
        $this->postJson($join, $payload, $headers)->assertOk()->assertJsonPath('redirect', route('class.show', $session->join_token));
        $this->get(route('class.show', $session->join_token))->assertSee('Data peserta tersimpan.');
        $this->postJson($join, $payload, $headers)->assertOk();
        $this->assertDatabaseCount('enrollments', 2);
        $url = route('class.l1.store', $session->join_token);
        $this->postJson($url, ['instrument_id' => $session->l1_instrument_id, 'answers' => [1, 2, 3, 4, 5]], $headers)
            ->assertOk()->assertJsonPath('redirect', $url);
        $this->postJson($url, ['answers' => [6, 6, 6, 6, 6]], $headers)->assertOk();
        $this->assertDatabaseCount('l1_responses', 1);
        $this->assertDatabaseHas('l1_responses', ['score_sum' => 15]);
    }

    public function test_async_validation_and_access_failures_are_not_success_redirects(): void
    {
        $session = LearningSession::firstOrFail();
        $headers = ['X-Lentera-Async' => '1'];
        $this->postJson(route('class.join', $session->join_token), [], $headers)->assertUnprocessable()->assertJsonValidationErrors('full_name');
        $this->postJson(route('class.l1.store', $session->join_token), [], $headers)->assertForbidden();
        $this->assertDatabaseCount('l1_responses', 0);
    }

    public function test_async_staff_login_preserves_session_and_logout(): void
    {
        $headers = ['X-Lentera-Async' => '1'];
        $this->postJson('/masuk', ['email' => 'pengelola@example.test', 'password' => 'Lentera-Demo-2026!'], $headers)
            ->assertOk()->assertJsonPath('redirect', url('/beranda'));
        $this->get('/beranda')->assertOk();
        $this->postJson('/keluar', [], $headers)->assertOk();
        $this->assertGuest();
    }
}
