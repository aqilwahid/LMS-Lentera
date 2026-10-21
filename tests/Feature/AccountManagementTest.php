<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use App\Notifications\QueuedResetPassword;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['lentera.demo_enabled' => true]);
        $this->seed(DemoSeeder::class);
    }

    private function user(string $alias): User
    {
        return User::where('email', $alias.'@example.test')->firstOrFail();
    }

    public function test_admin_creates_partner_who_sets_password_via_link_and_audit_is_written(): void
    {
        Notification::fake();
        $inst = Institution::first();
        $this->actingAs($this->user('pengelola'))
            ->post(route('accounts.store'), ['name' => 'Mitra Baru', 'email' => 'Baru@Example.test', 'role' => 'partner', 'institution_id' => $inst->id])
            ->assertRedirect();
        $new = User::where('email', 'baru@example.test')->firstOrFail();
        $this->assertSame('partner', $new->role);
        Notification::assertSentTo($new, QueuedResetPassword::class);
        $this->assertDatabaseHas('audit_entries', ['entity_type' => 'user', 'entity_id' => $new->id, 'action' => 'account.created']);
        $this->post('/keluar');

        $token = Password::createToken($new);
        $this->post(route('password.update'), ['token' => $token, 'email' => $new->email, 'password' => 'short1', 'password_confirmation' => 'short1'])->assertSessionHasErrors('password');
        $this->post(route('password.update'), ['token' => $token, 'email' => $new->email, 'password' => 'KataSandiBaru2026', 'password_confirmation' => 'KataSandiBaru2026'])->assertRedirect(route('login'));
        $this->post('/masuk', ['email' => $new->email, 'password' => 'KataSandiBaru2026'])->assertRedirect();
        $this->assertAuthenticatedAs($new->fresh());
    }

    public function test_partner_and_participant_cannot_manage_accounts_and_scope_is_per_office(): void
    {
        foreach (['mitra', 'peserta'] as $alias) {
            $this->actingAs($this->user($alias))->get(route('accounts.index'))->assertForbidden();
        }
        $office = DB::table('offices')->insertGetId(['name' => 'KPwBI lain', 'created_at' => now(), 'updated_at' => now()]);
        $other = (new User)->forceFill(['name' => 'Admin Lain', 'email' => 'lain@example.test', 'password' => 'x-Password-12345', 'role' => 'bi_admin', 'office_id' => $office])->save();
        $other = User::where('email', 'lain@example.test')->first();
        $admin = $this->user('pengelola');
        $this->actingAs($admin)->get(route('accounts.edit', $other->id))->assertNotFound();
        $this->put(route('accounts.update', $other->id), ['name' => 'X', 'is_active' => 0])->assertNotFound();
        $this->post(route('accounts.reset', $other->id))->assertNotFound();
        $this->get(route('accounts.index'))->assertOk()->assertDontSee('lain@example.test');
    }

    public function test_deactivating_blocks_login_but_not_self_deactivation(): void
    {
        $admin = $this->user('pengelola');
        $partner = $this->user('mitra');
        $this->actingAs($admin)->put(route('accounts.update', $admin->id), ['name' => $admin->name, 'is_active' => 0])->assertSessionHasErrors('is_active');
        $this->put(route('accounts.update', $partner->id), ['name' => $partner->name, 'is_active' => 0])->assertRedirect();
        $this->assertFalse($partner->fresh()->is_active);
        $this->post('/keluar');
        $this->post('/masuk', ['email' => $partner->email, 'password' => 'Lentera-Demo-2026!'])->assertSessionHasErrors('email');
    }

    public function test_forgot_password_does_not_enumerate_and_ignores_participants(): void
    {
        Notification::fake();
        $known = $this->post(route('password.email'), ['email' => 'mitra@example.test']);
        $unknown = $this->post(route('password.email'), ['email' => 'tidak-ada@example.test']);
        $participant = $this->post(route('password.email'), ['email' => 'peserta@example.test']);
        $this->assertSame(session('status'), $known->getSession()->get('status'));
        $this->assertSame($known->getSession()->get('status'), $unknown->getSession()->get('status'));
        $this->assertSame($known->getSession()->get('status'), $participant->getSession()->get('status'));
        Notification::assertSentToTimes($this->user('mitra'), QueuedResetPassword::class, 1);
        Notification::assertNotSentTo($this->user('peserta'), QueuedResetPassword::class);
    }

    public function test_reset_email_is_queued_and_scheduler_runs_bounded_worker(): void
    {
        Queue::fake();
        $this->post(route('password.email'), ['email' => 'mitra@example.test']);
        Queue::assertPushed(SendQueuedNotifications::class, fn ($job) => $job->notification instanceof QueuedResetPassword);
        $events = collect(app(Schedule::class)->events())->map(fn ($e) => $e->command);
        $this->assertTrue($events->contains(fn ($c) => str_contains($c, 'queue:work database --stop-when-empty')));
        $this->assertTrue($events->contains(fn ($c) => str_contains($c, 'auth:clear-resets')));
    }

    public function test_reset_rejects_invalid_token_and_participant_token(): void
    {
        $this->post(route('password.update'), ['token' => 'salah', 'email' => 'mitra@example.test', 'password' => 'KataSandiBaru2026', 'password_confirmation' => 'KataSandiBaru2026'])->assertSessionHasErrors('email');
        $participant = $this->user('peserta');
        $token = Password::createToken($participant);
        $this->post(route('password.update'), ['token' => $token, 'email' => $participant->email, 'password' => 'KataSandiBaru2026', 'password_confirmation' => 'KataSandiBaru2026'])->assertSessionHasErrors('email');
    }
}
