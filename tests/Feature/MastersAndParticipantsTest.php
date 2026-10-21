<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AuditEntry;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\L1Response;
use App\Models\LearningSession;
use App\Models\Speaker;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MastersAndParticipantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_speaker_selection_rejects_foreign_office_and_rebinding_after_join(): void
    {
        $this->actingAs($this->admin());
        $office = DB::table('offices')->insertGetId(['name' => 'KPwBI narasumber lain']);
        $foreign = Speaker::create(['office_id' => $office, 'name' => 'Narasumber kantor lain']);
        $this->post(route('management.sessions.store', Activity::first()), [...$this->sessionPayload(), 'speaker_id' => $foreign->id])
            ->assertSessionHasErrors('speaker_id');
        $this->assertDatabaseCount('learning_sessions', 1);
        $speaker = Speaker::create(['office_id' => $this->admin()->office_id, 'name' => 'Narasumber pengganti']);
        $this->unknown();
        $session = LearningSession::first();
        $this->put(route('management.sessions.update', $session), [...$this->sessionPayload(), 'title' => $session->title, 'speaker_id' => $speaker->id, 'revision' => 1])
            ->assertSessionHasErrors('title');
        $this->assertNull($session->fresh()->speaker_id);
        $this->assertDatabaseCount('audit_entries', 0);
    }

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

    private function unknown(): Enrollment
    {
        return Enrollment::create(['activity_id' => Activity::first()->id, 'joined_session_id' => LearningSession::first()->id, 'institution_name' => 'Alias Kampus Belum Dikenali', 'full_name' => 'Peserta Privat Uji', 'identity_number' => '0000123', 'email' => 'privat@example.test', 'phone' => '081200000000', 'status' => 'active']);
    }

    private function responseFor(Enrollment $e): void
    {
        $session = LearningSession::first();
        L1Response::create(['enrollment_id' => $e->id, 'learning_session_id' => $session->id, 'l1_instrument_id' => $session->l1_instrument_id, 'instrument_snapshot' => ['questions' => config('lentera.l1_questions'), 'code' => 'L1-DEMO', 'version' => 1], 'speaker_name' => $session->speaker_name, 'answers' => [5, 5, 5, 5, 5], 'score_sum' => 25, 'submitted_at' => now()]);
    }

    private function sessionPayload(): array
    {
        return ['title' => 'Sesi Master', 'speaker_name' => 'Nama yang tidak dipercaya', 'l1_instrument_id' => LearningSession::first()->l1_instrument_id, 'starts_at' => '2026-10-15T09:00', 'join_opens_at' => '2026-10-01T08:00', 'join_closes_at' => '2026-11-01T17:00', 'l1_opens_at' => '2026-10-01T08:00', 'l1_closes_at' => '2026-11-01T17:00'];
    }

    public function test_master_create_update_duplicate_and_stale_forms_are_scoped(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        foreach (['institusi', 'narasumber'] as $kind) {
            $model = $kind === 'institusi' ? Institution::class : Speaker::class;
            $this->get(route('masters.index', $kind))->assertOk();
            $this->get(route('masters.create', $kind))->assertOk();
            $data = ['name' => 'Master Uji '.$kind, 'is_active' => 1, 'organization' => 'Instansi Uji', 'type' => 'external', 'office_id' => 999];
            $this->post(route('masters.store', $kind), $data)->assertRedirect();
            $record = $model::where('name', $data['name'])->firstOrFail();
            $this->assertSame($admin->office_id, $record->office_id);
            $this->post(route('masters.store', $kind), $data)->assertSessionHasErrors('name');
            $url = route('masters.update', [$kind, $record->id]);
            $this->put($url, [...$data, 'name' => 'Nama Baru '.$kind, 'revision' => 1, 'is_active' => 0])->assertRedirect();
            $this->assertFalse($record->fresh()->is_active);
            $this->put($url, [...$data, 'revision' => 1])->assertSessionHasErrors('revision');
        }
        $this->assertDatabaseCount('audit_entries', 4);
    }

    public function test_mapping_preserves_original_and_changes_partner_scope_then_can_be_removed(): void
    {
        $e = $this->unknown();
        $this->responseFor($e);
        $partner = User::where('role', 'partner')->first();
        $this->actingAs($partner)->get('/beranda')->assertOk()->assertDontSee('5,00 / 6');
        $this->actingAs($this->admin())->put(route('participants.update', $e), ['revision' => 1, 'status' => 'active', 'mapped_institution_id' => $partner->institution_id, 'full_name' => 'Tampered', 'institution_name' => 'Tampered', 'institution_id' => 999])->assertRedirect();
        $fresh = $e->fresh();
        $this->assertNull($fresh->institution_id);
        $this->assertSame('Alias Kampus Belum Dikenali', $fresh->institution_name);
        $this->assertSame('Peserta Privat Uji', $fresh->full_name);
        $this->actingAs($partner)->get('/beranda')->assertOk()->assertSee('5,00 / 6')->assertDontSee('privat@example.test')->assertDontSee('Peserta Privat Uji');
        $this->actingAs($this->admin())->put(route('participants.update', $e), ['revision' => 2, 'status' => 'active', 'mapped_institution_id' => ''])->assertRedirect();
        $this->actingAs($partner)->get('/beranda')->assertOk()->assertDontSee('5,00 / 6');
        foreach (AuditEntry::where('entity_type', 'enrollment')->get() as $audit) {
            $json = json_encode($audit->changes);
            foreach (['Peserta Privat Uji', 'privat@example.test', '0000123', '081200000000'] as $pii) {
                $this->assertStringNotContainsString($pii, $json);
            }
        }
    }

    public function test_mapping_rejects_foreign_office_inactive_and_known_historical_institution(): void
    {
        $this->actingAs($this->admin());
        $e = $this->unknown();
        $office = DB::table('offices')->insertGetId(['name' => 'Kantor Lain']);
        $foreign = Institution::create(['name' => 'PT Asing', 'office_id' => $office]);
        $inactive = Institution::create(['name' => 'PT Nonaktif', 'office_id' => $this->admin()->office_id, 'is_active' => false]);
        foreach ([$foreign, $inactive] as $target) {
            $this->put(route('participants.update', $e), ['revision' => 1, 'status' => 'active', 'mapped_institution_id' => $target->id])->assertSessionHasErrors('mapped_institution_id');
        }
        $known = Enrollment::whereNotNull('institution_id')->first();
        $this->put(route('participants.update', $known), ['revision' => 1, 'status' => 'active', 'mapped_institution_id' => Institution::first()->id])->assertSessionHasErrors('mapped_institution_id');
        $this->assertDatabaseCount('audit_entries', 0);
    }

    public function test_status_changes_are_versioned_and_keep_historical_responses(): void
    {
        $e = $this->unknown();
        $this->responseFor($e);
        $this->actingAs($this->admin());
        $url = route('participants.update', $e);
        $this->put($url, ['revision' => 1, 'status' => 'revoked'])->assertRedirect();
        $this->assertSame('revoked', $e->fresh()->status);
        $this->put($url, ['revision' => 1, 'status' => 'active'])->assertSessionHasErrors('revision');
        $this->put($url, ['revision' => 2, 'status' => 'active'])->assertRedirect();
        $this->assertSame('active', $e->fresh()->status);
        $this->assertDatabaseCount('l1_responses', 1);
    }

    public function test_participant_pages_are_private_scoped_and_searchable(): void
    {
        $e = $this->unknown();
        $this->actingAs($this->admin());
        $this->get(route('participants.index', ['q' => '0000123', 'mapping' => 'unmapped']))->assertOk()->assertSee('Peserta Privat Uji')->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('participants.show', $e))->assertOk()->assertSee('privat@example.test')->assertHeader('Cache-Control', 'no-store, private');
        $office = DB::table('offices')->insertGetId(['name' => 'Kantor Rahasia']);
        $activity = Activity::create(['title' => 'Kegiatan Rahasia', 'office_id' => $office]);
        $other = Enrollment::create(['activity_id' => $activity->id, 'institution_name' => 'Instansi Rahasia', 'full_name' => 'Peserta Kantor Lain']);
        $this->get(route('participants.show', $other))->assertForbidden();
        $this->put(route('participants.update', $other), ['revision' => 1, 'status' => 'revoked'])->assertForbidden();
        $this->get(route('participants.index'))->assertDontSee('Peserta Kantor Lain');
        $this->get(route('participants.index', ['activity_id' => $activity->id]))->assertSessionHasErrors('activity_id');
    }

    public function test_guest_partner_participant_and_other_office_cannot_manage_masters_or_participants(): void
    {
        $e = $this->unknown();
        $master = Institution::first();
        $this->get(route('participants.show', $e))->assertRedirect('/masuk');
        foreach (['partner', 'participant'] as $role) {
            $this->actingAs(User::where('role', $role)->first());
            $this->get(route('masters.index', 'institusi'))->assertForbidden();
            $this->post(route('masters.store', 'narasumber'), ['name' => 'Forbidden', 'is_active' => 1, 'type' => 'external'])->assertForbidden();
            $this->get(route('participants.index'))->assertForbidden();
            $this->get(route('participants.show', $e))->assertForbidden();
            $this->put(route('participants.update', $e), ['revision' => 1, 'status' => 'revoked'])->assertForbidden();
        }
        $admin = $this->admin();
        $admin->forceFill(['office_id' => DB::table('offices')->insertGetId(['name' => 'Kantor berbeda'])])->save();
        $this->actingAs($admin)->get(route('masters.edit', ['institusi', $master->id]))->assertNotFound();
        $this->put(route('masters.update', ['institusi', $master->id]), ['name' => 'Hijack', 'is_active' => 1, 'revision' => 1])->assertNotFound();
    }

    public function test_master_speaker_name_is_server_snapshot_and_rename_does_not_change_session(): void
    {
        $this->actingAs($this->admin());
        $speaker = Speaker::create(['office_id' => $this->admin()->office_id, 'name' => 'Narasumber Master']);
        $this->post(route('management.sessions.store', Activity::first()), [...$this->sessionPayload(), 'speaker_id' => $speaker->id])->assertRedirect();
        $session = LearningSession::where('title', 'Sesi Master')->firstOrFail();
        $this->assertSame('Narasumber Master', $session->speaker_name);
        $this->assertSame($speaker->id, $session->speaker_id);
        $this->put(route('masters.update', ['narasumber', $speaker->id]), ['name' => 'Nama Master Berubah', 'type' => 'external', 'is_active' => 0, 'revision' => 1])->assertRedirect();
        $this->assertSame('Narasumber Master', $session->fresh()->speaker_name);
        $this->put(route('management.sessions.update', $session), [...$this->sessionPayload(), 'speaker_id' => $speaker->id, 'revision' => 1])->assertRedirect();
        $this->assertSame('Narasumber Master', $session->fresh()->speaker_name);
        $this->post(route('management.sessions.store', Activity::first()), [...$this->sessionPayload(), 'speaker_id' => $speaker->id])->assertSessionHasErrors('speaker_id');
    }

    public function test_inactive_institution_is_not_auto_assigned_and_rename_keeps_old_snapshot(): void
    {
        $institution = Institution::first();
        $original = Enrollment::first()->institution_name;
        $institution->update(['name' => 'Nama Institusi Baru', 'is_active' => false]);
        $this->assertSame($original, Enrollment::first()->fresh()->institution_name);
        $session = LearningSession::first();
        $url = route('class.join', $session->join_token);
        $this->get($url)->assertOk()->assertDontSee('<option value="Nama Institusi Baru">', false);
        $this->post($url, ['full_name' => 'Peserta Baru', 'institution_name' => 'Nama Institusi Baru', 'identity_number' => '001', 'department' => 'Unit', 'faculty' => 'Unit', 'phone' => '081200000000', 'participant_category' => 'Mahasiswa', 'email' => 'baru@example.test'])->assertRedirect();
        $this->assertNull(Enrollment::where('full_name', 'Peserta Baru')->first()->institution_id);
    }

    public function test_inactive_existing_mapping_can_be_preserved_on_status_change(): void
    {
        $e = $this->unknown();
        $institution = Institution::first();
        $e->update(['mapped_institution_id' => $institution->id]);
        $institution->update(['is_active' => false]);
        $this->actingAs($this->admin())->get(route('participants.show', $e))->assertOk()->assertSee($institution->name);
        $this->put(route('participants.update', $e), ['revision' => 1, 'status' => 'revoked', 'mapped_institution_id' => $institution->id])->assertRedirect();
        $this->assertSame($institution->id, $e->fresh()->mapped_institution_id);
    }

    public function test_participant_change_rolls_back_if_audit_write_fails(): void
    {
        $e = $this->unknown();
        $this->actingAs($this->admin());
        $this->withoutExceptionHandling();
        AuditEntry::creating(fn () => throw new \RuntimeException('Audit unavailable'));
        try {
            $this->put(route('participants.update', $e), ['revision' => 1, 'status' => 'revoked']);
            $this->fail('Expected exception');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Audit unavailable', $exception->getMessage());
        }
        $this->assertSame('active', $e->fresh()->status);
        $this->assertSame(1, $e->fresh()->revision);
    }

    public function test_identity_correction_is_audited_locked_and_scoped(): void
    {
        $e = $this->unknown();
        $this->responseFor($e);
        $payload = ['revision' => 1, 'full_name' => 'Peserta Privat Benar', 'identity_number' => '0000124', 'email' => 'Benar@Example.test', 'phone' => '081200000000', 'department' => 'Unit', 'faculty' => 'Unit', 'reason' => 'Salah ketik NIM'];
        $this->actingAs($this->admin());
        $this->put(route('participants.correct', $e), [...$payload, 'institution_name' => 'Tampered', 'participant_category' => 'Tampered'])->assertRedirect();
        $fresh = $e->fresh();
        $this->assertSame(['Peserta Privat Benar', '0000124', 'benar@example.test', 2, 'Alias Kampus Belum Dikenali'], [$fresh->full_name, $fresh->identity_number, $fresh->email, $fresh->revision, $fresh->institution_name]);
        $this->assertSame(1, L1Response::where('enrollment_id', $e->id)->count());
        $audit = AuditEntry::where('action', 'enrollment.corrected')->firstOrFail();
        $this->assertSame('0000123', $audit->changes['before']['identity_number']);
        $this->assertSame('Salah ketik NIM', $audit->changes['after']['reason']);
        $this->assertArrayNotHasKey('phone', $audit->changes['after']);
        // Stale revision, no-op, and missing reason are rejected.
        $this->put(route('participants.correct', $e), [...$payload, 'full_name' => 'Lain'])->assertSessionHasErrors('revision');
        $this->put(route('participants.correct', $e), [...$payload, 'revision' => 2, 'identity_number' => '0000124', 'full_name' => 'Peserta Privat Benar', 'email' => 'benar@example.test'])->assertSessionHasErrors('full_name');
        $this->put(route('participants.correct', $e), [...$payload, 'revision' => 2, 'full_name' => 'X', 'reason' => ''])->assertSessionHasErrors('reason');
        $this->assertSame(1, AuditEntry::where('action', 'enrollment.corrected')->count());
        // Other office and partner cannot correct.
        $other = Enrollment::create(['activity_id' => Activity::create(['title' => 'K', 'office_id' => DB::table('offices')->insertGetId(['name' => 'Kantor Lain'])])->id, 'institution_name' => 'X', 'full_name' => 'Y']);
        $this->put(route('participants.correct', $other), $payload)->assertForbidden();
        $this->actingAs(User::where('role', 'partner')->firstOrFail())->put(route('participants.correct', $e), [...$payload, 'revision' => 2])->assertForbidden();
    }
}
