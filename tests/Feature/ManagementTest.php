<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AuditEntry;
use App\Models\Enrollment;
use App\Models\L1Instrument;
use App\Models\LearningSession;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['lentera.demo_enabled' => true]);
        $this->seed(DemoSeeder::class);
    }

    private function admin(): User
    {
        return User::where('role', 'bi_admin')->firstOrFail();
    }

    private function payload(): array
    {
        return ['title' => 'Sesi Uji Pengelola', 'speaker_name' => 'Narasumber Uji', 'l1_instrument_id' => L1Instrument::first()->id,
            'starts_at' => now()->format('Y-m-d\TH:i'), 'join_opens_at' => now()->subDay()->format('Y-m-d\TH:i'),
            'join_closes_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'l1_opens_at' => now()->subDay()->format('Y-m-d\TH:i'), 'l1_closes_at' => now()->addDay()->format('Y-m-d\TH:i')];
    }

    public function test_admin_creates_activity_and_session_in_own_office_with_audit(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $this->get(route('management.activities.index'))->assertOk()->assertSee('Kelola kegiatan.');
        $this->get(route('management.activities.create'))->assertOk();
        $this->post(route('management.activities.store'), ['title' => 'Kegiatan Uji', 'description' => 'Deskripsi uji', 'office_id' => 999, 'is_demo' => false])->assertRedirect();
        $activity = Activity::where('title', 'Kegiatan Uji')->firstOrFail();
        $this->assertSame($admin->office_id, $activity->office_id);
        $this->assertTrue((bool) $activity->is_demo);
        $this->get(route('management.sessions.create', $activity))->assertOk();
        $this->post(route('management.sessions.store', $activity), [...$this->payload(), 'activity_id' => 999, 'join_enabled' => true, 'join_token' => 'predictable'])->assertRedirect();
        $session = LearningSession::where('title', 'Sesi Uji Pengelola')->firstOrFail();
        $this->assertSame($activity->id, $session->activity_id);
        $this->assertFalse($session->join_enabled);
        $this->assertSame(48, strlen($session->join_token));
        $this->assertNotSame('predictable', $session->join_token);
        $this->get(route('management.sessions.edit', $session))->assertOk()->assertSee('Aktifkan akses kelas');
        $this->assertDatabaseCount('audit_entries', 2);
        $audit = AuditEntry::where('action', 'session.created')->first();
        $this->assertSame($admin->id, $audit->actor_id);
        $this->assertStringNotContainsString($session->join_token, json_encode($audit->changes));
    }

    public function test_activity_update_is_versioned_and_rejects_stale_form(): void
    {
        $this->actingAs($this->admin());
        $activity = Activity::first();
        $url = route('management.activities.update', $activity);
        $this->put($url, ['title' => 'Judul baru', 'revision' => 1, 'office_id' => 999])->assertRedirect();
        $this->assertSame('Judul baru', $activity->fresh()->title);
        $this->assertSame(2, $activity->fresh()->revision);
        $this->put($url, ['title' => 'Overwrite tab lama', 'revision' => 1])->assertSessionHasErrors('revision');
        $this->assertSame('Judul baru', $activity->fresh()->title);
        $this->assertDatabaseCount('audit_entries', 1);
    }

    public function test_session_schedule_update_and_stale_retry(): void
    {
        $this->actingAs($this->admin());
        $session = LearningSession::first();
        $payload = [...$this->payload(), 'revision' => 1];
        $this->put(route('management.sessions.update', $session), $payload)->assertRedirect();
        $this->assertSame('Sesi Uji Pengelola', $session->fresh()->title);
        $this->assertSame(2, $session->fresh()->revision);
        $this->put(route('management.sessions.update', $session), $payload)->assertSessionHasErrors('revision');
        $this->assertDatabaseCount('audit_entries', 1);
    }

    public function test_invalid_schedules_and_draft_instruments_cannot_be_used(): void
    {
        $this->actingAs($this->admin());
        $url = route('management.sessions.store', Activity::first());
        $this->post($url, [])->assertSessionHasErrors();
        foreach (['join_closes_at' => '2000-01-01T00:00', 'l1_closes_at' => '2000-01-01T00:00', 'starts_at' => 'invalid'] as $key => $value) {
            $this->post($url, array_replace($this->payload(), [$key => $value]))->assertSessionHasErrors($key);
        }
        $draft = L1Instrument::create(['code' => 'DRAFT', 'version' => 1, 'questions' => config('lentera.l1_questions')]);
        $this->post($url, array_replace($this->payload(), ['l1_instrument_id' => $draft->id]))->assertSessionHasErrors('l1_instrument_id');
        $bad = L1Instrument::create(['code' => 'BAD', 'version' => 1, 'questions' => ['only one'], 'published_at' => now()]);
        $this->post($url, array_replace($this->payload(), ['l1_instrument_id' => $bad->id]))->assertSessionHasErrors('l1_instrument_id');
        $this->assertDatabaseCount('learning_sessions', 1);
        $this->assertDatabaseCount('audit_entries', 0);
    }

    public function test_context_locks_after_guest_joins_but_schedules_can_change(): void
    {
        $session = LearningSession::first();
        Enrollment::create(['activity_id' => $session->activity_id, 'joined_session_id' => $session->id, 'institution_name' => 'Institusi contoh', 'full_name' => 'Peserta uji', 'status' => 'active']);
        $this->actingAs($this->admin());
        $this->get(route('management.sessions.edit', $session))->assertOk()->assertSee('dikunci');
        $payload = [...$this->payload(), 'revision' => 1];
        $this->put(route('management.sessions.update', $session), $payload)->assertSessionHasErrors('title');
        $this->assertDatabaseCount('audit_entries', 0);
        $payload['title'] = $session->title;
        $payload['speaker_name'] = $session->speaker_name;
        $this->put(route('management.sessions.update', $session), $payload)->assertRedirect();
        $this->assertSame(2, $session->fresh()->revision);
    }

    public function test_qr_disable_reenable_rotate_and_stale_rotation_preserve_participants(): void
    {
        $session = LearningSession::first();
        $token = $session->join_token;
        $join = route('class.join', $token);
        $this->get($join)->assertOk();
        $this->actingAs($this->admin());
        $url = route('management.sessions.access', $session);
        $this->post($url, ['revision' => 1, 'action' => 'disable'])->assertRedirect();
        $this->get($join)->assertNotFound();
        $this->assertSame($token, $session->fresh()->join_token);
        $this->post($url, ['revision' => 2, 'action' => 'enable'])->assertRedirect();
        $this->get($join)->assertOk();
        $this->post($url, ['revision' => 3, 'action' => 'rotate'])->assertRedirect();
        $newToken = $session->fresh()->join_token;
        $this->assertNotSame($token, $newToken);
        $this->get($join)->assertNotFound();
        $this->get(route('class.join', $newToken))->assertOk();
        $this->post($url, ['revision' => 3, 'action' => 'rotate'])->assertSessionHasErrors('revision');
        $this->assertSame($newToken, $session->fresh()->join_token);
        $this->assertDatabaseCount('enrollments', 1);
        $this->assertDatabaseCount('audit_entries', 3);
        foreach (AuditEntry::all() as $entry) {
            $encoded = json_encode($entry->changes);
            $this->assertStringNotContainsString($token, $encoded);
            $this->assertStringNotContainsString($newToken, $encoded);
        }
    }

    public function test_guest_partner_and_participant_cannot_manage_or_read_audit(): void
    {
        $this->get(route('management.activities.index'))->assertRedirect('/masuk');
        foreach (['partner', 'participant'] as $role) {
            $this->actingAs(User::where('role', $role)->first());
            $this->get(route('management.activities.index'))->assertForbidden();
            $this->get(route('management.activities.create'))->assertForbidden();
            $this->post(route('management.activities.store'), ['title' => 'Forbidden'])->assertForbidden();
            $this->put(route('management.activities.update', Activity::first()), ['title' => 'Forbidden', 'revision' => 1])->assertForbidden();
            $this->post(route('management.sessions.store', Activity::first()), $this->payload())->assertForbidden();
            $this->put(route('management.sessions.update', LearningSession::first()), [...$this->payload(), 'revision' => 1])->assertForbidden();
            $this->post(route('management.sessions.access', LearningSession::first()), ['revision' => 1, 'action' => 'disable'])->assertForbidden();
            $this->get(route('management.audit'))->assertForbidden();
        }
        $this->assertDatabaseCount('audit_entries', 0);
    }

    public function test_admin_cannot_manage_other_office_or_see_its_audit(): void
    {
        $own = $this->admin();
        $office = DB::table('offices')->insertGetId(['name' => 'KPwBI lain']);
        $other = Activity::create(['office_id' => $office, 'title' => 'Kegiatan kantor lain']);
        AuditEntry::create(['actor_id' => $own->id, 'office_id' => $office, 'entity_type' => 'activity', 'entity_id' => $other->id, 'action' => 'activity.created', 'changes' => ['after' => ['title' => 'Rahasia kantor lain']], 'created_at' => now()]);
        $this->actingAs($own);
        $this->get(route('management.activities.index'))->assertOk()->assertDontSee($other->title);
        $this->get(route('management.activities.edit', $other))->assertForbidden();
        $this->put(route('management.activities.update', $other), ['title' => 'Hijack', 'revision' => 1])->assertForbidden();
        $this->post(route('management.sessions.store', $other), $this->payload())->assertForbidden();
        $this->get(route('management.audit'))->assertOk()->assertDontSee('Rahasia kantor lain');
    }

    public function test_transaction_rolls_back_when_audit_cannot_be_saved(): void
    {
        $this->actingAs($this->admin());
        $this->withoutExceptionHandling();
        AuditEntry::creating(fn () => throw new \RuntimeException('Audit unavailable'));
        try {
            $this->post(route('management.activities.store'), ['title' => 'Must rollback']);
            $this->fail('Expected audit exception');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Audit unavailable', $exception->getMessage());
        }
        $this->assertDatabaseMissing('activities', ['title' => 'Must rollback']);
        $this->assertDatabaseCount('audit_entries', 0);
    }
}
