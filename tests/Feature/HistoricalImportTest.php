<?php

namespace Tests\Feature;

use App\Models\AuditEntry;
use App\Models\Enrollment;
use App\Models\ImportBatch;
use App\Models\Institution;
use App\Models\L1Response;
use App\Models\LearningSession;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HistoricalImportTest extends TestCase
{
    use RefreshDatabase;

    private const HEAD = 'timestamp;nama;instansi;kategori;nim;email;nilai_1;nilai_2;nilai_3;nilai_4;nilai_5;masukan';

    private const ROWS = [
        '2026-04-07 10:15:00;Ani;Universitas Sultan Ageng Tirtayasa;Mahasiswa / GenBI;0012345;ani@example.test;6;5;4;3;2;=cmd',
        '07/04/2026 10:16:00;Budi;Kampus Lama Banten;Mahasiswa / GenBI;0099;budi@example.test;1;1;1;1;1;',
        '2026-04-07 10:17:00;Ani Lagi;Universitas Sultan Ageng Tirtayasa;Mahasiswa / GenBI;0012345;ani@example.test;6;6;6;6;6;',
        '2026-04-07 10:18:00;Cici;Universitas Sultan Ageng Tirtayasa;Mahasiswa / GenBI;77;cici@example.test;7;5;4;3;2;',
        ';;;;;;;;;;;',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['lentera.demo_enabled' => true]);
        $this->seed(DemoSeeder::class);
    }

    private function csv(string $name = 'april.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, implode("\n", [self::HEAD, ...self::ROWS]));
    }

    public function test_import_stages_maps_alias_applies_once_and_detects_duplicates(): void
    {
        $admin = User::where('email', 'pengelola@example.test')->firstOrFail();
        $session = LearningSession::firstOrFail();
        $this->actingAs($admin)->post(route('imports.store'), ['learning_session_id' => $session->id, 'file' => $this->csv()])->assertRedirect();
        $batch = ImportBatch::firstOrFail();
        $this->assertEqualsCanonicalizing(['ok' => 1, 'unmapped' => 1, 'duplicate' => 1, 'error' => 1, 'summary' => 1], DB::table('import_rows')->pluck('status')->countBy()->all());
        $this->assertSame(0, L1Response::count());

        // Same file again is refused; unmapped names are never guessed.
        $this->actingAs($admin)->post(route('imports.store'), ['learning_session_id' => $session->id, 'file' => $this->csv('lagi.csv')])->assertSessionHasErrors('file');
        $this->assertSame(1, ImportBatch::count());

        $this->actingAs($admin)->get(route('imports.show', $batch->id))->assertOk()->assertSee('Kampus Lama Banten');
        $target = Institution::where('name', 'UIN Sultan Maulana Hasanuddin Banten')->firstOrFail();
        $this->actingAs($admin)->post(route('imports.alias', $batch->id), ['institution_key' => 'kampus lama banten', 'institution_id' => $target->id])->assertRedirect();
        $this->actingAs($admin)->post(route('imports.apply', $batch->id))->assertRedirect();
        $this->actingAs($admin)->post(route('imports.apply', $batch->id)); // idempotent

        $this->assertSame(2, L1Response::count());
        $ani = L1Response::where('enrollment_id', Enrollment::where('identity_number', '0012345')->value('id'))->firstOrFail();
        $this->assertSame(20, $ani->score_sum);
        $this->assertSame('2026-04-07 10:15:00', $ani->submitted_at->format('Y-m-d H:i:s'));
        $this->assertSame($target->id, Enrollment::where('identity_number', '0099')->firstOrFail()->institution_id);
        $this->assertSame(['import.staged', 'import.alias', 'import.applied'], AuditEntry::where('entity_type', 'import_batch')->orderBy('id')->pluck('action')->all());
    }

    public function test_import_is_admin_only_and_scoped_to_own_office(): void
    {
        $session = LearningSession::firstOrFail();
        $mitra = User::where('email', 'mitra@example.test')->firstOrFail();
        $this->actingAs($mitra)->get(route('imports.index'))->assertForbidden();
        $this->actingAs($mitra)->post(route('imports.store'), ['learning_session_id' => $session->id, 'file' => $this->csv()])->assertForbidden();
        $other = DB::table('offices')->insertGetId(['name' => 'KPwBI Lain', 'created_at' => now(), 'updated_at' => now()]);
        $admin2 = (new User)->forceFill(['name' => 'Lain', 'email' => 'lain@example.test', 'password' => 'Lentera-Demo-2026!', 'role' => 'bi_admin', 'office_id' => $other, 'is_active' => true]);
        $admin2->save();
        $this->actingAs($admin2)->post(route('imports.store'), ['learning_session_id' => $session->id, 'file' => $this->csv()])->assertNotFound();
    }
}
