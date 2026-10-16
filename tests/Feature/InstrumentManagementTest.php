<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AuditEntry;
use App\Models\L1Instrument;
use App\Models\L1InstrumentSet;
use App\Models\L1Response;
use App\Models\LearningSession;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class InstrumentManagementTest extends TestCase
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
        return ['name' => 'Instrumen uji lokal', 'questions' => ['Aspek satu', 'Aspek dua', 'Aspek tiga', 'Aspek empat', 'Aspek lima'], 'creation_nonce' => Str::uuid()->toString()];
    }

    private function draft(): L1Instrument
    {
        $this->actingAs($this->admin())->post(route('instruments.store'), $this->payload())->assertRedirect();

        return L1Instrument::whereNotNull('l1_instrument_set_id')->latest('id')->firstOrFail();
    }

    private function published(): L1Instrument
    {
        $draft = $this->draft();
        $this->post(route('instruments.publish', $draft), ['revision' => 1])->assertRedirect();

        return $draft->fresh();
    }

    private function sessionPayload(int $instrumentId): array
    {
        return ['title' => 'Sesi instrumen uji', 'speaker_name' => 'Narasumber uji', 'l1_instrument_id' => $instrumentId,
            'starts_at' => now()->format('Y-m-d\TH:i'), 'join_opens_at' => now()->subDay()->format('Y-m-d\TH:i'),
            'join_closes_at' => now()->addDay()->format('Y-m-d\TH:i'), 'l1_opens_at' => now()->subDay()->format('Y-m-d\TH:i'),
            'l1_closes_at' => now()->addDay()->format('Y-m-d\TH:i')];
    }

    public function test_create_uses_server_identity_and_renders_draft_and_shared_reference(): void
    {
        $this->actingAs($this->admin());
        $this->get(route('instruments.index'))->assertOk()->assertSee('L1-DEMO');
        $this->get(route('instruments.create'))->assertOk()->assertSee('questions[4]', false);
        $payload = $this->payload();
        $this->post(route('instruments.store'), [...$payload, 'office_id' => 999, 'code' => 'INJECT', 'version' => 99, 'published_at' => now(), 'l1_instrument_set_id' => 999])->assertRedirect();
        $draft = L1Instrument::latest('id')->first();
        $this->assertSame($payload['questions'], $draft->questions);
        $this->assertSame($this->admin()->office_id, $draft->set->office_id);
        $this->assertSame($payload['name'], $draft->set->name);
        $this->assertSame(1, $draft->version);
        $this->assertNull($draft->published_at);
        $this->assertNotSame('INJECT', $draft->code);
        $this->get(route('instruments.show', $draft))->assertOk()->assertSee('Ubah draf')->assertSee('Terbitkan versi 1');
        $this->assertDatabaseCount('audit_entries', 1);
        $this->assertStringNotContainsString($payload['creation_nonce'], json_encode(AuditEntry::first()->changes));
    }

    public function test_create_retry_preserves_first_record_and_rejects_different_payload(): void
    {
        $this->actingAs($this->admin());
        $payload = $this->payload();
        $this->post(route('instruments.store'), $payload)->assertRedirect();
        $draft = L1Instrument::latest('id')->first();
        $this->post(route('instruments.store'), $payload)->assertRedirect(route('instruments.show', $draft));
        $this->post(route('instruments.store'), [...$payload, 'name' => 'Different data'])->assertSessionHasErrors('creation_nonce');
        $this->assertDatabaseCount('l1_instrument_sets', 1);
        $this->assertDatabaseCount('l1_instruments', 2);
        $this->assertDatabaseCount('audit_entries', 1);
    }

    public function test_exactly_five_text_questions_and_nonce_are_required(): void
    {
        $this->actingAs($this->admin());
        foreach ([[], ['one'], ['a', 'b', 'c', 'd', 'e', 'f'], ['a', 'b', 'c', 'd', ['nested']], ['a', 'b', 'c', 'd', ' '], ['a', 'b', 'c', 'd', str_repeat('x', 2001)], [1 => 'a', 2 => 'b', 3 => 'c', 4 => 'd', 5 => 'e']] as $questions) {
            $this->postJson(route('instruments.store'), [...$this->payload(), 'questions' => $questions])->assertUnprocessable();
        }
        $this->postJson(route('instruments.store'), [...$this->payload(), 'name' => ''])->assertJsonValidationErrors('name');
        $this->postJson(route('instruments.store'), [...$this->payload(), 'creation_nonce' => 'invalid'])->assertJsonValidationErrors('creation_nonce');
        $this->assertDatabaseCount('l1_instrument_sets', 0);
        $this->assertDatabaseCount('audit_entries', 0);
    }

    public function test_update_and_publish_reject_stale_revisions_and_publication_is_idempotent(): void
    {
        $draft = $this->draft();
        $questions = ['Baru satu', 'Baru dua', 'Baru tiga', 'Baru empat', 'Baru lima'];
        $this->put(route('instruments.update', $draft), ['questions' => $questions, 'revision' => 1, 'published_at' => now(), 'code' => 'INJECT'])->assertRedirect();
        $this->assertNull($draft->fresh()->published_at);
        $this->assertSame($questions, $draft->fresh()->questions);
        $this->put(route('instruments.update', $draft), ['questions' => $this->payload()['questions'], 'revision' => 1])->assertSessionHasErrors('revision');
        $this->post(route('instruments.publish', $draft), ['revision' => 1])->assertSessionHasErrors('revision');
        $this->post(route('instruments.publish', $draft), ['revision' => 2])->assertRedirect();
        $publishedAt = $draft->fresh()->published_at->toIso8601String();
        $this->travel(1)->hours();
        $this->post(route('instruments.publish', $draft), ['revision' => 2])->assertRedirect();
        $this->assertSame($publishedAt, $draft->fresh()->published_at->toIso8601String());
        $this->assertSame(3, $draft->fresh()->revision);
        $this->put(route('instruments.update', $draft), ['questions' => $questions, 'revision' => 3])->assertConflict();
        $this->assertDatabaseCount('audit_entries', 3);
    }

    public function test_malformed_persisted_draft_cannot_be_published(): void
    {
        $draft = $this->draft();
        DB::table('l1_instruments')->where('id', $draft->id)->update(['questions' => json_encode(['bad'])]);
        $this->post(route('instruments.publish', $draft), ['revision' => 1])->assertSessionHasErrors('questions');
        $this->assertNull($draft->fresh()->published_at);
    }

    public function test_new_versions_copy_published_source_and_retry_does_not_allocate_again(): void
    {
        $published = $this->published();
        $data = ['creation_nonce' => Str::uuid()->toString(), 'questions' => ['Ignore injected values'], 'version' => 99];
        $this->post(route('instruments.version', $published), $data)->assertRedirect();
        $v2 = L1Instrument::latest('id')->first();
        $this->assertSame(2, $v2->version);
        $this->assertSame($published->questions, $v2->questions);
        $this->assertNull($v2->published_at);
        $this->post(route('instruments.version', $published), $data)->assertRedirect(route('instruments.show', $v2));
        $this->post(route('instruments.version', $v2), ['creation_nonce' => Str::uuid()->toString()])->assertConflict();
        $this->post(route('instruments.version', $published), ['creation_nonce' => Str::uuid()->toString()])->assertRedirect();
        $this->assertSame(3, L1Instrument::latest('id')->first()->version);
        $this->assertDatabaseCount('l1_instruments', 4);
        $this->assertDatabaseCount('audit_entries', 4);
    }

    public function test_shared_published_reference_is_read_only_and_can_be_copied_to_own_series(): void
    {
        $shared = L1Instrument::first();
        $this->actingAs($this->admin());
        $this->get(route('instruments.show', $shared))->assertOk()->assertSee('Salin sebagai instrumen baru');
        $this->get(route('instruments.create', ['template' => $shared->id]))->assertOk()->assertSee($shared->questions[0]);
        $this->put(route('instruments.update', $shared), ['questions' => $shared->questions, 'revision' => 1])->assertForbidden();
        $this->post(route('instruments.publish', $shared), ['revision' => 1])->assertForbidden();
        $this->post(route('instruments.version', $shared), ['creation_nonce' => Str::uuid()->toString()])->assertForbidden();
        $draft = L1Instrument::create(['code' => 'SHARED-DRAFT', 'version' => 1, 'questions' => $shared->questions]);
        $this->get(route('instruments.show', $draft))->assertForbidden();
        $this->get(route('instruments.index'))->assertDontSee('SHARED-DRAFT');
    }

    public function test_other_office_is_blocked_in_views_templates_and_session_assignment(): void
    {
        $office = DB::table('offices')->insertGetId(['name' => 'KPwBI lain']);
        $set = L1InstrumentSet::create(['name' => 'Instrumen kantor lain', 'code' => 'OTHER', 'office_id' => $office]);
        $other = $set->instruments()->create(['code' => $set->code, 'version' => 1, 'questions' => $this->payload()['questions'], 'published_at' => now()]);
        $this->actingAs($this->admin());
        $this->get(route('instruments.index'))->assertOk()->assertDontSee($set->name);
        $this->get(route('instruments.show', $other))->assertForbidden();
        $this->get(route('instruments.create', ['template' => $other->id]))->assertForbidden();
        $this->put(route('instruments.update', $other), ['questions' => $other->questions, 'revision' => 1])->assertForbidden();
        $this->post(route('instruments.publish', $other), ['revision' => 1])->assertForbidden();
        $this->post(route('instruments.version', $other), ['creation_nonce' => Str::uuid()->toString()])->assertForbidden();
        $activity = Activity::first();
        $this->get(route('management.sessions.create', $activity))->assertOk()->assertDontSee($set->name);
        $this->post(route('management.sessions.store', $activity), $this->sessionPayload($other->id))->assertSessionHasErrors('l1_instrument_id');
        $session = LearningSession::first();
        $this->put(route('management.sessions.update', $session), [...$this->sessionPayload($other->id), 'revision' => $session->revision])->assertSessionHasErrors('l1_instrument_id');
        $this->assertDatabaseCount('learning_sessions', 1);
    }

    public function test_guests_other_roles_and_inactive_admin_cannot_manage_instruments(): void
    {
        $instrument = L1Instrument::first();
        $this->get(route('instruments.index'))->assertRedirect('/masuk');
        foreach (['partner', 'participant'] as $role) {
            $this->actingAs(User::where('role', $role)->first());
            $this->get(route('instruments.index'))->assertForbidden();
            $this->get(route('instruments.create'))->assertForbidden();
            $this->get(route('instruments.show', $instrument))->assertForbidden();
            $this->post(route('instruments.store'), $this->payload())->assertForbidden();
            $this->put(route('instruments.update', $instrument), [])->assertForbidden();
            $this->post(route('instruments.publish', $instrument), [])->assertForbidden();
            $this->post(route('instruments.version', $instrument), [])->assertForbidden();
        }
        $admin = $this->admin();
        $admin->forceFill(['is_active' => false])->save();
        $this->actingAs($admin)->get(route('instruments.index'))->assertForbidden();
        $this->assertDatabaseCount('audit_entries', 0);
    }

    public function test_draft_unavailable_and_published_version_integrates_with_guest_l1_history(): void
    {
        $draft = $this->draft();
        $activity = Activity::first();
        $this->post(route('management.sessions.store', $activity), $this->sessionPayload($draft->id))->assertSessionHasErrors('l1_instrument_id');
        $this->post(route('instruments.publish', $draft), ['revision' => 1])->assertRedirect();
        $this->post(route('management.sessions.store', $activity), $this->sessionPayload($draft->id))->assertRedirect();
        $session = LearningSession::latest('id')->first();
        $session->update(['join_enabled' => true]);
        auth()->logout();
        $this->get(route('class.join', $session->join_token))->assertOk();
        $this->post(route('class.register', $session->join_token), ['full_name' => 'Peserta uji instrumen', 'institution_name' => 'Institusi uji', 'identity_number' => '001', 'department' => 'Ekonomi', 'faculty' => 'Ekonomi', 'phone' => '08123456789', 'participant_category' => 'Mahasiswa', 'email' => 'instrument@example.test'])->assertRedirect();
        $this->get(route('class.l1.edit', $session->join_token))->assertOk()->assertSee($draft->questions[0]);
        $this->post(route('class.l1.store', $session->join_token), ['instrument_id' => $draft->id, 'answers' => [1, 2, 3, 4, 5]])->assertRedirect();
        $response = L1Response::firstOrFail();
        $this->assertSame($draft->questions, $response->instrument_snapshot['questions']);
        $this->assertSame(15, $response->score_sum);
        $this->assertGuest();
        $this->actingAs($this->admin());
        $this->post(route('instruments.version', $draft), ['creation_nonce' => Str::uuid()->toString()])->assertRedirect();
        $v2 = L1Instrument::latest('id')->first();
        $questions = ['Revisi satu', 'Revisi dua', 'Revisi tiga', 'Revisi empat', 'Revisi lima'];
        $this->put(route('instruments.update', $v2), ['questions' => $questions, 'revision' => 1])->assertRedirect();
        $this->post(route('instruments.publish', $v2), ['revision' => 2])->assertRedirect();
        $this->put(route('management.sessions.update', $session), [...$this->sessionPayload($v2->id), 'revision' => $session->fresh()->revision])->assertSessionHasErrors('title');
        $this->assertSame($draft->id, $session->fresh()->l1_instrument_id);
        $this->assertSame($draft->questions, $response->fresh()->instrument_snapshot['questions']);
        $this->assertSame(1, $response->fresh()->instrument_snapshot['version']);
        $this->post(route('management.sessions.store', $activity), $this->sessionPayload($v2->id))->assertRedirect();
        $this->assertSame($v2->id, LearningSession::latest('id')->first()->l1_instrument_id);
    }

    public function test_series_and_draft_identity_cannot_be_rewritten_through_models(): void
    {
        $draft = $this->draft();
        foreach ([fn () => $draft->update(['version' => 9]), fn () => $draft->fresh()->update(['creation_key' => 'changed']), fn () => $draft->set->update(['office_id' => 999]), fn () => $draft->set->delete()] as $mutation) {
            try {
                $mutation();
                $this->fail('Identity mutation should fail');
            } catch (\LogicException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
    }

    public function test_audit_failure_rolls_back_creation_updates_publication_and_version(): void
    {
        $draft = $this->draft();
        $published = $this->published();
        $instrumentCount = L1Instrument::count();
        $this->withoutExceptionHandling();
        AuditEntry::creating(fn () => throw new \RuntimeException('Audit unavailable'));
        try {
            foreach ([
                fn () => $this->post(route('instruments.store'), $this->payload()),
                fn () => $this->put(route('instruments.update', $draft), ['questions' => config('lentera.l1_questions'), 'revision' => 1]),
                fn () => $this->post(route('instruments.publish', $draft), ['revision' => 1]),
                fn () => $this->post(route('instruments.version', $published), ['creation_nonce' => Str::uuid()->toString()]),
            ] as $mutation) {
                try {
                    $mutation();
                    $this->fail('Expected audit exception');
                } catch (\RuntimeException $exception) {
                    $this->assertSame('Audit unavailable', $exception->getMessage());
                }
            }
            $this->assertSame($instrumentCount, L1Instrument::count());
            $this->assertDatabaseCount('l1_instrument_sets', 2);
            $this->assertNull($draft->fresh()->published_at);
            $this->assertSame(1, $draft->fresh()->revision);
            $this->assertSame($this->payload()['questions'], $draft->fresh()->questions);
        } finally {
            AuditEntry::flushEventListeners();
        }
    }
}
