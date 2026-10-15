<?php

namespace Tests\Feature;

use App\Models\AuditEntry;
use App\Models\Enrollment;
use App\Models\LearningSession;
use App\Models\Material;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PrivateMaterialsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['lentera.demo_enabled' => true]);
        $this->seed(DemoSeeder::class);
        Storage::fake('materials');
    }

    private function admin(): User
    {
        return User::where('role', 'bi_admin')->firstOrFail();
    }

    private function pdf(string $marker = 'one'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('materi.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n% ".$marker."\n%%EOF");
    }

    private function upload(?string $nonce = null): Material
    {
        $this->actingAs($this->admin())->post(route('materials.store', LearningSession::first()), ['title' => 'Materi Privat Uji', 'description' => 'Materi contoh', 'pdf' => $this->pdf(), 'upload_nonce' => $nonce ?? (string) Str::uuid()])->assertRedirect();

        return Material::firstOrFail();
    }

    private function publish(Material $m, int $version = 1): void
    {
        $m->refresh();
        $this->actingAs($this->admin())->post(route('materials.publish', $m), ['revision' => $m->revision, 'operation' => 'publish', 'version_id' => $m->versions()->where('version_number', $version)->first()->id])->assertRedirect();
    }

    private function join(): void
    {
        $this->app['auth']->forgetGuards();
        $session = LearningSession::first();
        $url = route('class.join', $session->join_token);
        $this->get($url);
        $this->post($url, ['full_name' => 'Peserta Materi Uji', 'institution_name' => 'Universitas Sultan Ageng Tirtayasa', 'identity_number' => '0000123', 'department' => 'Ekonomi', 'faculty' => 'Ekonomi', 'phone' => '081200000000', 'participant_category' => 'Mahasiswa', 'email' => 'materi@example.test'])->assertRedirect();
    }

    private function guestUrl(Material $m, string $route = 'class.material.download'): string
    {
        return route($route, [LearningSession::first()->join_token, $m]);
    }

    public function test_upload_is_private_draft_with_hash_and_no_file_path_in_html_or_audit(): void
    {
        $m = $this->upload();
        $v = $m->versions()->first();
        $this->assertFalse($m->is_visible);
        $this->assertNull($m->current_version_id);
        Storage::disk('materials')->assertExists($v->storage_path);
        $this->assertSame(hash('sha256', Storage::disk('materials')->get($v->storage_path)), $v->sha256);
        $this->get(route('materials.show', $m))->assertOk()->assertDontSee($v->storage_path)->assertDontSee($v->upload_key);
        $audit = AuditEntry::where('action', 'material.uploaded')->firstOrFail();
        $this->assertStringNotContainsString($v->storage_path, json_encode($audit->changes));
        $this->join();
        $this->get($this->guestUrl($m))->assertNotFound();
    }

    public function test_registered_guest_reads_current_pdf_inline_and_downloads_it(): void
    {
        $m = $this->upload();
        $this->publish($m);
        $this->join();
        $session = LearningSession::first();
        $this->get(route('class.show', $session->join_token))->assertOk()->assertSee('Materi Privat Uji');
        $this->get($this->guestUrl($m, 'class.material.preview'))->assertOk()->assertSee('Pratinjau PDF')->assertSee('Unduh PDF');
        $inline = $this->get($this->guestUrl($m, 'class.material.file'))->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringStartsWith('inline;', $inline->headers->get('Content-Disposition'));
        $download = $this->get($this->guestUrl($m))->assertOk();
        $this->assertStringStartsWith('attachment;', $download->headers->get('Content-Disposition'));
    }

    public function test_upload_retry_and_changed_content_do_not_create_duplicate_versions_or_files(): void
    {
        $nonce = (string) Str::uuid();
        $m = $this->upload($nonce);
        $this->upload($nonce);
        $this->assertDatabaseCount('materials', 1);
        $this->assertDatabaseCount('material_versions', 1);
        $this->assertCount(1, Storage::disk('materials')->allFiles());
        $this->post(route('materials.store', LearningSession::first()), ['title' => 'Changed', 'pdf' => $this->pdf('different'), 'upload_nonce' => $nonce])->assertSessionHasErrors('pdf');
        $this->assertDatabaseCount('material_versions', 1);
        $this->assertCount(1, Storage::disk('materials')->allFiles());
    }

    public function test_new_version_preserves_published_version_until_explicit_publication(): void
    {
        $m = $this->upload();
        $this->publish($m);
        $old = $m->fresh()->current_version_id;
        $nonce = (string) Str::uuid();
        $data = ['revision' => $m->fresh()->revision, 'pdf' => $this->pdf('two'), 'upload_nonce' => $nonce];
        $this->post(route('materials.version', $m), $data)->assertRedirect();
        $this->post(route('materials.version', $m), ['revision' => $data['revision'], 'pdf' => $this->pdf('two'), 'upload_nonce' => $nonce])->assertRedirect();
        $this->assertDatabaseCount('material_versions', 2);
        $this->assertCount(2, Storage::disk('materials')->allFiles());
        $this->assertSame($old, $m->fresh()->current_version_id);
        $this->publish($m, 2);
        $this->assertNotSame($old, $m->fresh()->current_version_id);
        $this->join();
        $response = $this->get($this->guestUrl($m))->assertOk();
        $this->assertStringContainsString('-v2.pdf', $response->headers->get('Content-Disposition'));
        $this->get($this->guestUrl($m).'?version='.$old)->assertStatus(409);
    }

    public function test_invalid_file_extension_content_size_and_nonce_are_rejected(): void
    {
        $this->actingAs($this->admin());
        $url = route('materials.store', LearningSession::first());
        foreach ([
            ['pdf' => UploadedFile::fake()->createWithContent('fake.pdf', '<?php echo 1;')],
            ['pdf' => UploadedFile::fake()->createWithContent('fake.html', "%PDF-1.4\n%%EOF")],
            ['upload_nonce' => 'invalid'],
        ] as $invalid) {
            $this->post($url, array_replace(['title' => 'Invalid', 'pdf' => $this->pdf(), 'upload_nonce' => (string) Str::uuid()], $invalid))->assertSessionHasErrors();
        }
        config(['lentera.material_max_kb' => 1]);
        $this->post($url, ['title' => 'Large', 'pdf' => $this->pdf(str_repeat('x', 3000)), 'upload_nonce' => (string) Str::uuid()])->assertSessionHasErrors('pdf');
        $this->assertDatabaseCount('materials', 0);
        $this->assertCount(0, Storage::disk('materials')->allFiles());
    }

    public function test_guest_partner_participant_and_other_office_cannot_upload_or_download_admin_versions(): void
    {
        $m = $this->upload();
        $v = $m->versions()->first();
        foreach (['partner', 'participant'] as $role) {
            $this->actingAs(User::where('role', $role)->first());
            $this->get(route('materials.index', LearningSession::first()))->assertForbidden();
            $this->post(route('materials.store', LearningSession::first()), [])->assertForbidden();
            $this->post(route('materials.publish', $m), [])->assertForbidden();
            $this->get(route('materials.admin.download', [$m, $v]))->assertForbidden();
        }
        $admin = $this->admin();
        $admin->forceFill(['office_id' => DB::table('offices')->insertGetId(['name' => 'Kantor lain'])])->save();
        $this->actingAs($admin)->get(route('materials.show', $m))->assertForbidden();
        $this->post(route('materials.version', $m), [])->assertForbidden();
        $this->get(route('materials.admin.download', [$m, $v]))->assertForbidden();
    }

    public function test_qr_alone_revoked_enrollment_and_disabled_qr_cannot_fetch_private_pdf(): void
    {
        $m = $this->upload();
        $this->publish($m);
        $this->app['session']->flush();
        $this->get($this->guestUrl($m))->assertForbidden();
        $this->join();
        $this->get($this->guestUrl($m))->assertOk();
        $e = Enrollment::where('full_name', 'Peserta Materi Uji')->first();
        $e->update(['status' => 'revoked']);
        $this->get($this->guestUrl($m))->assertForbidden();
        $e->update(['status' => 'active']);
        $session = LearningSession::first();
        $session->update(['join_enabled' => false]);
        $this->get($this->guestUrl($m))->assertNotFound();
    }

    public function test_material_from_another_class_and_foreign_version_cannot_be_selected(): void
    {
        $m = $this->upload();
        $this->publish($m);
        $session = LearningSession::first();
        $other = $session->replicate();
        $other->join_token = Str::random(48);
        $other->save();
        $this->join();
        $this->get(route('class.material.download', [$other->join_token, $m]))->assertForbidden();
        $this->actingAs($this->admin())->post(route('materials.store', $other), ['title' => 'Other', 'pdf' => $this->pdf('other'), 'upload_nonce' => (string) Str::uuid()])->assertRedirect();
        $otherM = Material::where('title', 'Other')->first();
        $otherV = $otherM->versions()->first();
        $this->post(route('materials.publish', $m), ['revision' => $m->fresh()->revision, 'operation' => 'publish', 'version_id' => $otherV->id])->assertSessionHasErrors('version_id');
        $this->get(route('materials.admin.download', [$m, $otherV]))->assertNotFound();
    }

    public function test_hide_and_stale_publication_do_not_delete_versions(): void
    {
        $m = $this->upload();
        $revision = $m->revision;
        $this->publish($m);
        $this->post(route('materials.publish', $m), ['revision' => $revision, 'operation' => 'hide'])->assertSessionHasErrors('revision');
        $this->post(route('materials.publish', $m), ['revision' => $m->fresh()->revision, 'operation' => 'hide'])->assertRedirect();
        $this->assertFalse($m->fresh()->is_visible);
        $this->assertDatabaseCount('material_versions', 1);
        $this->join();
        $this->get($this->guestUrl($m))->assertNotFound();
    }

    public function test_failed_database_audit_removes_file_and_rolls_back_metadata(): void
    {
        $this->actingAs($this->admin());
        $this->withoutExceptionHandling();
        AuditEntry::creating(fn () => throw new \RuntimeException('Audit unavailable'));
        try {
            $this->post(route('materials.store', LearningSession::first()), ['title' => 'Rollback', 'pdf' => $this->pdf(), 'upload_nonce' => (string) Str::uuid()]);
            $this->fail('Expected exception');
        } catch (\RuntimeException $e) {
            $this->assertSame('Audit unavailable', $e->getMessage());
        }
        $this->assertDatabaseCount('materials', 0);
        $this->assertDatabaseCount('material_versions', 0);
        $this->assertCount(0, Storage::disk('materials')->allFiles());
    }

    public function test_missing_file_is_not_published_and_version_metadata_is_immutable(): void
    {
        $m = $this->upload();
        $v = $m->versions()->first();
        Storage::disk('materials')->delete($v->storage_path);
        $this->post(route('materials.publish', $m), ['revision' => 1, 'operation' => 'publish', 'version_id' => $v->id])->assertStatus(409);
        $this->expectException(\LogicException::class);
        $v->update(['storage_path' => 'other.pdf']);
    }
}
