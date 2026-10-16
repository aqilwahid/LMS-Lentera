<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\L2Assessment;
use App\Models\L2Attempt;
use App\Models\L2Instrument;
use App\Models\LearningSession;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuestL2Test extends TestCase
{
    use RefreshDatabase;

    private LearningSession $session;

    private L2Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['lentera.demo_enabled' => true]);
        $this->seed(DemoSeeder::class);
        $this->session = LearningSession::firstOrFail();
        $instrument = L2Instrument::create(['office_id' => $this->session->activity->office_id, 'code' => 'L2-TEST', 'name' => 'L2 sintetis', 'version' => 1,
            'creation_key' => hash('sha256', 'fixture'), 'creation_hash' => hash('sha256', 'fixture'), 'published_at' => now(), 'instructions' => 'Petunjuk sintetis',
            'questions' => array_map(fn ($i) => ['text' => 'Pertanyaan '.$i, 'options' => ['Pilihan A', 'Pilihan B', 'Pilihan C', 'Pilihan D'], 'correct' => $i], range(0, 2))]);
        $this->assessment = L2Assessment::create(['learning_session_id' => $this->session->id, 'l2_instrument_id' => $instrument->id,
            'pre_opens_at' => now()->subDay(), 'pre_closes_at' => now()->addDay(), 'post_opens_at' => now()->subDay(), 'post_closes_at' => now()->addDay(), 'enabled' => true]);
    }

    private function register(): void
    {
        $url = route('class.join', $this->session->join_token);
        $this->get($url)->assertOk();
        $this->post($url, ['full_name' => 'Peserta L2 sintetis', 'institution_name' => 'Universitas Sultan Ageng Tirtayasa', 'identity_number' => '0001', 'department' => 'Ekonomi', 'faculty' => 'Ekonomi', 'phone' => '08123456789', 'participant_category' => 'Mahasiswa', 'email' => 'l2@example.test'])->assertRedirect();
    }

    private function url(string $stage = 'pre', ?string $operation = null): string
    {
        return $operation ? route('class.l2.mutate', [$this->session->join_token, $stage, $operation]) : route('class.l2.show', [$this->session->join_token, $stage]);
    }

    private function start(string $stage = 'pre'): L2Attempt
    {
        $this->post($this->url($stage, 'start'))->assertRedirect($this->url($stage));

        return L2Attempt::where('stage', $stage)->latest('id')->firstOrFail();
    }

    public function test_database_unique_constraint_rejects_duplicate_attempt_for_same_participant_and_stage(): void
    {
        $this->register();
        $attributes = $this->start()->getAttributes();
        unset($attributes['id']);
        $this->expectException(QueryException::class);
        DB::table('l2_attempts')->insert($attributes);
    }

    public function test_guest_draft_reload_and_final_are_scored_from_snapshot_without_key_or_unreleased_score(): void
    {
        $this->register();
        $this->get(route('class.show', $this->session->join_token))->assertOk()->assertSee('Buka pretest')->assertSee('Buka post-test');
        $this->get($this->url())->assertOk()->assertDontSee('Pertanyaan 0');
        $attempt = $this->start();
        $this->get($this->url())->assertOk()->assertSee('Petunjuk sintetis')->assertSee('Pertanyaan 0')->assertDontSee('Kunci:')->assertDontSee('"correct"', false)->assertHeader('Cache-Control', 'no-store, private');
        $this->post($this->url('pre', 'save'), ['revision' => 1, 'answers' => [2 => 2]])->assertRedirect();
        $this->assertSame([2 => 2], $attempt->fresh()->answers);
        $this->assertNull($attempt->fresh()->score_percent);
        $this->get($this->url())->assertOk()->assertSee('1 / 3')->assertSee('id="l2-2-2"', false);
        $this->post($this->url('pre', 'submit'), ['revision' => 2, 'answers' => [0, 1, 3], 'score_percent' => 100, 'correct_count' => 3, 'enrollment_id' => 999, 'instrument_snapshot' => ['correct' => 3]])->assertRedirect();
        $this->assertSame(2, $attempt->fresh()->correct_count);
        $this->assertSame('66.67', $attempt->fresh()->score_percent);
        $this->assertNotNull($attempt->fresh()->submitted_at);
        $this->get($this->url())->assertOk()->assertSee('Pretest sudah dikirim.')->assertSee('Skor belum dibuka')->assertDontSee('66,67')->assertDontSee('"correct"', false);
        $this->assertArrayNotHasKey('instrument_snapshot', $attempt->toArray());
        $this->assertGuest();
        $this->assertDatabaseCount('users', 3);
    }

    public function test_start_and_final_retry_preserve_single_attempt_even_after_closure_and_disable(): void
    {
        $this->register();
        $attempt = $this->start();
        $this->start();
        $this->post($this->url('pre', 'submit'), ['revision' => 1, 'answers' => [0, 1, 2]])->assertRedirect();
        $original = $attempt->fresh()->getAttributes();
        $this->assessment->update(['enabled' => false, 'pre_closes_at' => now()->subHour()]);
        $this->post($this->url('pre', 'submit'), ['answers' => [3, 3, 3]])->assertRedirect();
        $this->post($this->url('pre', 'save'), [])->assertRedirect();
        $this->post($this->url('pre', 'start'), [])->assertRedirect();
        $this->assertSame($original, $attempt->fresh()->getAttributes());
        $this->assertDatabaseCount('l2_attempts', 1);
    }

    public function test_invalid_answers_incomplete_submission_and_stale_draft_do_not_change_saved_data(): void
    {
        $this->register();
        $attempt = $this->start();
        foreach ([[0], [0, 1, 2, 3], [0 => 4, 1 => 1, 2 => 2], [0 => ['nested']], ['evil' => 1], [-1 => 0], ['01' => 0]] as $answers) {
            $this->postJson($this->url('pre', 'submit'), ['revision' => 1, 'answers' => $answers])->assertUnprocessable();
        }
        $this->assertSame([], $attempt->fresh()->answers);
        $this->post($this->url('pre', 'save'), ['revision' => 1, 'answers' => [0 => 1]])->assertRedirect();
        $this->post($this->url('pre', 'save'), ['revision' => 1, 'answers' => [0 => 3]])->assertSessionHasErrors('revision');
        $this->post($this->url('pre', 'submit'), ['revision' => 1, 'answers' => [0, 1, 2]])->assertSessionHasErrors('revision');
        $this->assertSame([1], $attempt->fresh()->answers);
        $this->post($this->url('pre', 'save'), ['revision' => 2, 'answers' => []])->assertRedirect();
        $this->assertSame([], $attempt->fresh()->answers);
        $this->assertNull($attempt->fresh()->submitted_at);
    }

    public function test_schedule_and_disabled_tests_reject_new_starts_and_saves_preserving_draft(): void
    {
        $this->register();
        $this->assessment->update(['pre_opens_at' => now()->addHour(), 'pre_closes_at' => now()->addHours(2)]);
        $this->post($this->url('pre', 'start'))->assertForbidden();
        $this->assertDatabaseCount('l2_attempts', 0);
        $this->assessment->update(['pre_opens_at' => now()->subHour()]);
        $attempt = $this->start();
        $this->assessment->update(['enabled' => false]);
        $this->post($this->url('pre', 'save'), ['revision' => 1, 'answers' => [0 => 0]])->assertForbidden();
        $this->post($this->url('pre', 'submit'), ['revision' => 1, 'answers' => [0, 1, 2]])->assertForbidden();
        $this->get($this->url())->assertOk()->assertSee('Draf di bawah tetap tersimpan');
        $this->assertSame([], $attempt->fresh()->answers);
    }

    public function test_post_requirement_is_configurable_and_pre_draft_does_not_satisfy_it(): void
    {
        $this->register();
        $this->assessment->update(['require_pretest' => true]);
        $this->post($this->url('post', 'start'))->assertForbidden();
        $this->start('pre');
        $this->post($this->url('pre', 'save'), ['revision' => 1, 'answers' => [0, 1, 2]])->assertRedirect();
        $this->post($this->url('post', 'start'))->assertForbidden();
        $this->post($this->url('pre', 'submit'), ['revision' => 2, 'answers' => [0, 1, 2]])->assertRedirect();
        $this->start('post');
        $this->assertDatabaseCount('l2_attempts', 2);
    }

    public function test_post_can_be_started_without_pre_when_requirement_is_off_and_l1_is_not_a_prerequisite(): void
    {
        $this->register();
        $post = $this->start('post');
        $this->assertSame('post', $post->stage);
        $this->assertDatabaseCount('l1_responses', 0);
        $this->assertDatabaseCount('l2_attempts', 1);
    }

    public function test_lower_authoring_limit_does_not_invalidate_existing_published_version_or_grading(): void
    {
        config(['lentera.l2_max_questions' => 1]);
        $this->register();
        $attempt = $this->start();
        $this->post($this->url('pre', 'submit'), ['revision' => 1, 'answers' => [0, 1, 2]])->assertRedirect();
        $this->assertSame(3, $attempt->fresh()->question_count);
        $this->assertSame('100.00', $attempt->fresh()->score_percent);
    }

    public function test_other_browser_class_revoked_enrollment_and_changed_qr_cannot_use_attempt(): void
    {
        $this->register();
        $attempt = $this->start();
        $enrollment = Enrollment::findOrFail($attempt->enrollment_id);
        $enrollment->update(['status' => 'revoked']);
        $this->post($this->url('pre', 'save'), ['revision' => 1, 'answers' => [0 => 0]])->assertForbidden();
        $enrollment->update(['status' => 'active']);
        $otherSession = $this->session->replicate(['id']);
        $otherSession->join_token = Str::random(48);
        $otherSession->save();
        $this->post(route('class.l2.mutate', [$otherSession->join_token, 'pre', 'save']), ['revision' => 1, 'attempt_id' => $attempt->id])->assertForbidden();
        $this->flushSession();
        $this->get($this->url())->assertRedirect(route('class.join', $this->session->join_token));
        $this->post($this->url('pre', 'submit'), ['attempt_id' => $attempt->id, 'enrollment_id' => $enrollment->id])->assertForbidden();
        $oldURL = $this->url();
        $this->session->update(['join_token' => Str::random(48)]);
        $this->get($oldURL)->assertNotFound();
        $this->assertSame([], $attempt->fresh()->answers);
    }

    public function test_release_score_and_pair_summary_exclude_unpaired_results_and_keep_versions_separate(): void
    {
        $this->register();
        $this->start('pre');
        $this->post($this->url('pre', 'submit'), ['revision' => 1, 'answers' => [0, 1, 3]])->assertRedirect();
        $post = $this->start('post');
        $this->post($this->url('post', 'submit'), ['revision' => 1, 'answers' => [0, 1, 2]])->assertRedirect();
        $this->get($this->url('post'))->assertDontSee('100,00')->assertDontSee('33,33');
        $this->assessment->update(['results_visible' => true]);
        $this->get($this->url('post'))->assertOk()->assertSee('100,00 / 100')->assertSee('33,33 poin persentase')->assertDontSee('Kunci:');
        $unpaired = $post->replicate(['id']);
        $unpaired->forceFill(['stage' => 'pre', 'enrollment_id' => Enrollment::whereNull('guest_access_hash')->first()->id, 'submitted_at' => now(), 'score_percent' => 0, 'correct_count' => 0])->save();
        $this->actingAs(User::where('role', 'bi_admin')->first())->get(route('l2.settings.edit', $this->session))->assertOk()
            ->assertSee('Pretest final: 2')->assertSee('Pasangan lengkap: 1')->assertSee('66,67 / 100')->assertSee('33,33 poin persentase');
        $this->assertSame(1, $post->instrument_snapshot['version']);
    }

    public function test_first_attempt_freezes_instrument_and_prerequisite_but_allows_schedule_and_release(): void
    {
        $this->register();
        $this->start();
        $owner = User::where('role', 'bi_admin')->first();
        $this->actingAs($owner);
        $instrument = $this->assessment->instrument->replicate(['id']);
        $instrument->forceFill(['code' => 'L2-OTHER', 'creation_key' => hash('sha256', 'other')])->save();
        $payload = ['revision' => 1, 'l2_instrument_id' => $instrument->id, 'enabled' => 1, 'require_pretest' => 0, 'results_visible' => 0,
            'pre_opens_at' => now()->subDay()->format('Y-m-d\TH:i'), 'pre_closes_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'post_opens_at' => now()->subDay()->format('Y-m-d\TH:i'), 'post_closes_at' => now()->addDay()->format('Y-m-d\TH:i')];
        $url = route('l2.settings.update', $this->session);
        $this->put($url, $payload)->assertSessionHasErrors('l2_instrument_id');
        $payload['l2_instrument_id'] = $this->assessment->l2_instrument_id;
        $this->put($url, [...$payload, 'require_pretest' => 1])->assertSessionHasErrors('l2_instrument_id');
        $this->put($url, [...$payload, 'enabled' => 0, 'results_visible' => 1])->assertRedirect();
        $this->assertTrue($this->assessment->fresh()->results_visible);
        $this->assertFalse($this->assessment->fresh()->enabled);
    }

    public function test_snapshot_and_final_results_are_immutable_and_persisted_as_separate_stage_records(): void
    {
        $this->register();
        $attempt = $this->start();
        foreach ([fn () => $attempt->update(['instrument_snapshot' => []]), fn () => $attempt->fresh()->update(['stage' => 'post']), fn () => $attempt->fresh()->delete(), fn () => $this->assessment->update(['require_pretest' => true])] as $mutation) {
            try {
                $mutation();
                $this->fail('Mutation should fail');
            } catch (\LogicException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
        $this->post($this->url('pre', 'submit'), ['revision' => 1, 'answers' => [0, 1, 2]])->assertRedirect();
        $this->expectException(\LogicException::class);
        $attempt->fresh()->update(['answers' => [3, 3, 3]]);
    }
}
