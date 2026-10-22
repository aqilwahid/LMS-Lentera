<?php

namespace App\Console\Commands;

use App\Models\AuditEntry;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'lentera:create-admin {office : Nama KPwBI} {name : Nama pengelola} {email : Email pengelola}';

    protected $description = 'Buat KPwBI (jika belum ada) dan akun pengelola BI pertamanya';

    public function handle(): int
    {
        $password = $this->secret('Kata sandi (min. 12 karakter)');
        $v = Validator::make(
            ['email' => $this->argument('email'), 'password' => $password],
            ['email' => 'required|email|unique:users,email', 'password' => 'required|min:12']
        );
        if ($v->fails()) {
            $this->error($v->errors()->first());

            return self::FAILURE;
        }

        DB::transaction(function () use ($password) {
            $officeId = DB::table('offices')->where('name', $this->argument('office'))->value('id')
                ?? DB::table('offices')->insertGetId(['name' => $this->argument('office'), 'created_at' => now(), 'updated_at' => now()]);
            $user = new User(['name' => $this->argument('name'), 'email' => $this->argument('email'), 'password' => $password]);
            $user->forceFill(['role' => 'bi_admin', 'office_id' => $officeId, 'is_active' => true])->save();
            AuditEntry::create(['actor_id' => null, 'office_id' => $officeId, 'entity_type' => 'user', 'entity_id' => $user->id, 'action' => 'account.created', 'changes' => ['before' => [], 'after' => ['name' => $user->name, 'email' => $user->email, 'role' => 'bi_admin', 'is_active' => true]], 'created_at' => now()]);
        });
        $this->info('Akun pengelola dibuat.');

        return self::SUCCESS;
    }
}
