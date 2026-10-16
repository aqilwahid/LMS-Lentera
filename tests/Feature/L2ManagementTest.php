<?php

namespace Tests\Feature;

use App\Models\AuditEntry;
use App\Models\L2Assessment;
use App\Models\L2Instrument;
use App\Models\LearningSession;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class L2ManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['lentera.demo_enabled' => true]);
        $this->seed(DemoSeeder::class);
        $this->actingAs(User::where('role', 'bi_admin')->firstOrFail());
    }

    private function payload(): array
    {
        return ['name' => 'L2 uji sintetis', 'instructions' => 'Pilih satu jawaban.', 'creation_nonce' => Str::uuid()->toString(),
            'questions' => [
                ['text' => 'Soal satu', 'options' => ['Satu A', 'Satu B', 'Satu C', 'Satu D'], 'correct' => 0],
                ['text' => 'Soal dua', 'options' => ['Dua A', 'Dua B', 'Dua C', 'Dua D'], 'correct' => 1],
            ]];
    }

    private function draft(): L2Instrument
    {
        $this->post(route('l2.instruments.store'), $this->payload())->assertRedirect();

        return L2Instrument::latest('id')->firstOrFail();
    }

    private function published(): L2Instrument
    {
        $draft = $this->draft();
        $this->post(route('l2.instruments.publish', $draft), ['revision' => 1])->assertRedirect();

        return $draft->fresh();
    }

    private function settings(L2Instrument $instrument, int $revision = 0): array
    {
        return ['revision' => $revision, 'l2_instrument_id' => $instrument->id, 'enabled' => 1, 'require_pretest' => 0, 'results_visible' => 0,
            'pre_opens_at' => now()->subDay()->format('Y-m-d\TH:i'), 'pre_closes_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'post_opens_at' => now()->subDay()->format('Y-m-d\TH:i'), 'post_closes_at' => now()->addDay()->format('Y-m-d\TH:i')];
    }

    public function test_authoring_publication_and_versions_are_scoped_versioned_and_idempotent(): void
    {
        $this->get(route('l2.instruments.index'))->assertOk();
        $this->get(route('l2.instruments.create', ['count' => 2]))->assertOk()->assertSee('questions[1][correct]', false);
        $payload = $this->payload();
        $this->post(route('l2.instruments.store'), [...$payload, 'office_id' => 999, 'version' => 99, 'published_at' => now()])->assertRedirect();
        $draft = L2Instrument::firstOrFail();
        $this->assertSame(auth()->user()->office_id, $draft->office_id);
        $this->assertSame(1, $draft->version);
        $this->assertNull($draft->published_at);
        $this->post(route('l2.instruments.store'), $payload)->assertRedirect(route('l2.instruments.show', $draft));
        $this->post(route('l2.instruments.store'), [...$payload, 'name' => 'Changed'])->assertSessionHasErrors('creation_nonce');
        $this->get(route('l2.instruments.show', $draft))->assertOk()->assertSee('Kunci: A')->assertHeader('Cache-Control', 'no-store, private');
        $this->put(route('l2.instruments.update', $draft), [...$payload, 'revision' => 1, 'instructions' => 'Petunjuk baru'])->assertRedirect();
        $this->put(route('l2.instruments.update', $draft), [...$payload, 'revision' => 1])->assertSessionHasErrors('revision');
        $this->post(route('l2.instruments.publish', $draft), ['revision' => 1])->assertSessionHasErrors('revision');
        $this->post(route('l2.instruments.publish', $draft), ['revision' => 2])->assertRedirect();
        $time = $draft->fresh()->published_at->toIso8601String();
        $this->travel(1)->hours();
        $this->post(route('l2.instruments.publish', $draft), ['revision' => 2])->assertRedirect();
        $this->assertSame($time, $draft->fresh()->published_at->toIso8601String());
        $this->put(route('l2.instruments.update', $draft), [...$payload, 'revision' => 3])->assertConflict();
        $nonce = ['creation_nonce' => Str::uuid()->toString()];
        $this->post(route('l2.instruments.version', $draft), $nonce)->assertRedirect();
        $v2 = L2Instrument::latest('id')->first();
        $this->assertSame(2, $v2->version);
        $this->assertSame($draft->fresh()->questions, $v2->questions);
        $this->assertSame('Petunjuk baru', $v2->instructions);
        $this->assertNull($v2->published_at);
        $this->post(route('l2.instruments.version', $draft), $nonce)->assertRedirect(route('l2.instruments.show', $v2));
        $this->post(route('l2.instruments.version', $v2), ['creation_nonce' => Str::uuid()->toString()])->assertConflict();
        $this->assertDatabaseCount('l2_instruments', 2);
        $this->assertDatabaseCount('audit_entries', 4);
        $this->assertArrayNotHasKey('questions', $draft->toArray());
        $this->assertStringNotContainsString('Satu A', json_encode(AuditEntry::first()->changes));
    }

    public function test_invalid_questions_keys_nonce_and_corrupted_draft_are_rejected(): void
    {
        $valid = $this->payload();
        foreach ([[], [['text' => 'No options']], [['text' => 'Bad key', 'options' => ['A', 'B', 'C', 'D'], 'correct' => 4]],
            [['text' => ' ', 'options' => ['A', 'B', 'C', 'D'], 'correct' => 0]],
            [['text' => 'Extra option', 'options' => ['A', 'B', 'C', 'D', 'E'], 'correct' => 0]],
            [['text' => 'Nested', 'options' => [['bad'], 'B', 'C', 'D'], 'correct' => 0]],
            [1 => $valid['questions'][0]], array_fill(0, 51, $valid['questions'][0]),
        ] as $questions) {
            $this->postJson(route('l2.instruments.store'), [...$valid, 'questions' => $questions])->assertUnprocessable();
        }
        $this->postJson(route('l2.instruments.store'), [...$valid, 'creation_nonce' => 'bad'])->assertJsonValidationErrors('creation_nonce');
        $draft = $this->draft();
        DB::table('l2_instruments')->where('id', $draft->id)->update(['questions' => json_encode([['text' => 'corrupt']])]);
        $this->post(route('l2.instruments.publish', $draft), ['revision' => 1])->assertSessionHasErrors('questions');
        $this->assertNull($draft->fresh()->published_at);
    }

    public function test_settings_validate_published_ownership_schedule_and_stale_form(): void
    {
        $session = LearningSession::first();
        $url = route('l2.settings.update', $session);
        $draft = $this->draft();
        $this->get(route('l2.settings.edit', $session))->assertOk()->assertSee('Rata-rata berpasangan belum tersedia.');
        $this->put($url, $this->settings($draft))->assertSessionHasErrors('l2_instrument_id');
        $this->post(route('l2.instruments.publish', $draft), ['revision' => 1])->assertRedirect();
        $this->put($url, [...$this->settings($draft), 'pre_closes_at' => now()->subDays(2)->format('Y-m-d\TH:i')])->assertSessionHasErrors('pre_closes_at');
        $this->put($url, $this->settings($draft))->assertRedirect();
        $this->put($url, $this->settings($draft))->assertSessionHasErrors('revision');
        $this->put($url, [...$this->settings($draft, 1), 'results_visible' => 1])->assertRedirect();
        $this->assertTrue(L2Assessment::first()->results_visible);
        $this->assertSame(2, L2Assessment::first()->revision);
        $this->assertDatabaseCount('l2_assessments', 1);
    }

    public function test_other_office_roles_and_inactive_users_cannot_read_keys_or_manage_settings(): void
    {
        $instrument = $this->published();
        $session = LearningSession::first();
        $owner = auth()->user();
        $office = DB::table('offices')->insertGetId(['name' => 'KPwBI lain']);
        $other = User::create(['name' => 'Other', 'email' => 'other@example.test', 'password' => 'secret']);
        $other->forceFill(['office_id' => $office, 'role' => 'bi_admin'])->save();
        $other->refresh();
        foreach ([$other, User::where('role', 'partner')->first(), User::where('role', 'participant')->first()] as $user) {
            $this->actingAs($user);
            $this->get(route('l2.instruments.show', $instrument))->assertForbidden();
            $this->put(route('l2.instruments.update', $instrument), $this->payload())->assertForbidden();
            $this->post(route('l2.instruments.publish', $instrument), ['revision' => 1])->assertForbidden();
            $this->post(route('l2.instruments.version', $instrument), ['creation_nonce' => Str::uuid()->toString()])->assertForbidden();
            $this->get(route('l2.settings.edit', $session))->assertForbidden();
            $this->put(route('l2.settings.update', $session), $this->settings($instrument))->assertForbidden();
        }
        $this->actingAs($other)->get(route('l2.instruments.index'))->assertOk()->assertDontSee($instrument->name);
        foreach (['partner', 'participant'] as $role) {
            $this->actingAs(User::where('role', $role)->first())->post(route('l2.instruments.store'), $this->payload())->assertForbidden();
        }
        // An owner cannot assign a published instrument from another office either.
        $foreign = $instrument->replicate(['id']);
        $foreign->forceFill(['code' => 'FOREIGN', 'office_id' => $office, 'creation_key' => hash('sha256', 'foreign')])->save();
        $this->actingAs($owner)->put(route('l2.settings.update', $session), $this->settings($foreign))->assertSessionHasErrors('l2_instrument_id');
        $owner->forceFill(['is_active' => false])->save();
        $this->actingAs($owner)->get(route('l2.instruments.index'))->assertForbidden();
        auth()->logout();
        $this->get(route('l2.instruments.show', $instrument))->assertRedirect('/masuk');
    }

    public function test_audit_failure_rolls_back_every_instrument_write_and_assignment(): void
    {
        $draft = $this->draft();
        $published = $this->published();
        $this->withoutExceptionHandling();
        AuditEntry::creating(fn () => throw new \RuntimeException('Audit unavailable'));
        try {
            foreach ([fn () => $this->post(route('l2.instruments.store'), $this->payload()),
                fn () => $this->put(route('l2.instruments.update', $draft), [...$this->payload(), 'revision' => 1]),
                fn () => $this->post(route('l2.instruments.publish', $draft), ['revision' => 1]),
                fn () => $this->post(route('l2.instruments.version', $published), ['creation_nonce' => Str::uuid()->toString()]),
                fn () => $this->put(route('l2.settings.update', LearningSession::first()), $this->settings($published)),
            ] as $mutation) {
                try {
                    $mutation();
                    $this->fail('Expected audit exception');
                } catch (\RuntimeException $exception) {
                    $this->assertSame('Audit unavailable', $exception->getMessage());
                }
            }
            $this->assertDatabaseCount('l2_instruments', 2);
            $this->assertDatabaseCount('l2_assessments', 0);
            $this->assertNull($draft->fresh()->published_at);
            $this->assertSame(1, $draft->fresh()->revision);
        } finally {
            AuditEntry::flushEventListeners();
        }
    }

    public function test_model_protects_published_instrument_and_version_identity(): void
    {
        $published = $this->published();
        $draft = $this->draft();
        foreach ([fn () => $published->update(['instructions' => 'Changed after publication']), fn () => $published->delete(),
            fn () => $draft->update(['office_id' => 999]), fn () => $draft->fresh()->update(['version' => 99]),
        ] as $mutation) {
            try {
                $mutation();
                $this->fail('Mutation should be rejected');
            } catch (\LogicException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
    }
}
