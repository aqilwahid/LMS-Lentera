<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_office_admin_and_audit_and_rejects_weak_password(): void
    {
        $args = ['office' => 'KPwBI Contoh', 'name' => 'Admin', 'email' => 'admin@bi.test'];
        $this->artisan('lentera:create-admin', $args)->expectsQuestion('Kata sandi (min. 12 karakter)', 'pendek')->assertFailed();
        $this->assertDatabaseCount('users', 0);

        $this->artisan('lentera:create-admin', $args)->expectsQuestion('Kata sandi (min. 12 karakter)', 'Kata-Sandi-Kuat-1')->assertSuccessful();
        $u = User::where('email', 'admin@bi.test')->firstOrFail();
        $this->assertSame('bi_admin', $u->role);
        $this->assertDatabaseHas('audit_entries', ['entity_id' => $u->id, 'action' => 'account.created', 'office_id' => $u->office_id]);
    }
}
