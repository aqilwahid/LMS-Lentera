<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\L1Instrument;
use App\Models\LearningSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) || ! config('lentera.demo_enabled')) {
            throw new \RuntimeException('DemoSeeder memerlukan local/testing dan LENTERA_DEMO_ENABLED=true.');
        }
        DB::transaction(function () {
            $office = DB::table('offices')->where('name', 'KPwBI Banten (contoh)')->first();
            $officeId = $office?->id ?? DB::table('offices')->insertGetId(['name' => 'KPwBI Banten (contoh)', 'created_at' => now(), 'updated_at' => now()]);
            $institution = Institution::firstOrCreate(['name' => 'Universitas Sultan Ageng Tirtayasa'], ['office_id' => $officeId]);
            Institution::firstOrCreate(['name' => 'UIN Sultan Maulana Hasanuddin Banten'], ['office_id' => $officeId]);
            Institution::firstOrCreate(['name' => 'UIN Syarif Hidayatullah Jakarta'], ['office_id' => $officeId]);
            foreach (['participant' => 'peserta', 'bi_admin' => 'pengelola', 'partner' => 'mitra'] as $role => $alias) {
                $user = User::firstOrNew(['email' => $alias.'@example.test']);
                if (! $user->exists) {
                    $user->forceFill(['name' => ucfirst($alias).' Contoh', 'password' => 'Lentera-Demo-2026!', 'role' => $role, 'institution_id' => $role === 'bi_admin' ? null : $institution->id, 'office_id' => $role === 'bi_admin' ? $officeId : null, 'category' => 'Mahasiswa / GenBI'])->save();
                }
            }
            $instrument = L1Instrument::firstOrCreate(['code' => 'L1-DEMO', 'version' => 1], ['questions' => config('lentera.l1_questions'), 'published_at' => now()]);
            $activity = Activity::firstOrCreate(['title' => 'Literasi Kebanksentralan — kegiatan contoh'], ['office_id' => $officeId, 'description' => 'Data sintetis untuk pemeriksaan backend. Bukan data historis April.', 'is_demo' => true]);
            $session = LearningSession::firstOrCreate(['activity_id' => $activity->id, 'title' => 'Peran dan tugas Bank Indonesia'], ['l1_instrument_id' => $instrument->id, 'speaker_name' => 'Narasumber Contoh (belum ditetapkan)', 'starts_at' => now(), 'l1_opens_at' => now()->subDay(), 'l1_closes_at' => now()->addDays(30)]);
            if (! $session->join_token) {
                $session->update(['join_token' => Str::random(48), 'join_enabled' => true, 'join_opens_at' => now()->subDay(), 'join_closes_at' => now()->addDays(30)]);
            }
            Enrollment::firstOrCreate(['user_id' => User::where('email', 'peserta@example.test')->firstOrFail()->id, 'activity_id' => $activity->id], ['institution_id' => $institution->id, 'institution_name' => $institution->name, 'participant_category' => 'Mahasiswa / GenBI']);
        });
    }
}
