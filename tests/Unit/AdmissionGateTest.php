<?php

namespace Tests\Unit;

use App\Admission\AdmissionHttp;
use App\Admission\FileAdmissionGate;
use PHPUnit\Framework\TestCase;

class AdmissionGateTest extends TestCase
{
    private string $directory;

    private float $time = 1000000;

    private array $handles = [];

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/lentera-gate-test-'.bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        foreach ($this->handles as $handle) {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
        foreach (glob($this->directory.'/*') ?: [] as $file) {
            unlink($file);
        }
        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    private function gate(array $config = []): FileAdmissionGate
    {
        return new FileAdmissionGate($this->directory, array_replace(['capacity' => 1, 'starts_per_second' => 100], $config), fn () => $this->time);
    }

    private function acquire(FileAdmissionGate $gate, string $owner, ?string $ticket = null, string $target = 'POST /kelas/synthetic'): array
    {
        $this->time += 1;
        $result = $gate->evaluate($owner, $target, $ticket, true);
        if (isset($result['permit'])) {
            $this->handles[] = $result['permit'];
        }

        return $result;
    }

    public function test_running_requests_are_bounded_and_newcomers_do_not_overtake(): void
    {
        $gate = $this->gate();
        $active = $this->acquire($gate, 'first');
        $this->assertSame('ready', $active['state']);
        $second = $this->acquire($gate, 'second');
        $third = $this->acquire($gate, 'third');
        $this->assertSame(1, $second['position']);
        $this->assertSame(2, $third['position']);
        fclose($active['permit']);
        $late = $this->acquire($gate, 'newcomer');
        $this->assertSame('waiting', $late['state']);
        $this->assertSame('waiting', $this->acquire($gate, 'third', $third['ticket'])['state']);
        $next = $this->acquire($gate, 'second', $second['ticket']);
        $this->assertSame('ready', $next['state']);
        fclose($next['permit']);
        $this->assertSame('ready', $this->acquire($gate, 'third', $third['ticket'])['state']);
    }

    public function test_active_lock_does_not_expire_but_abandoned_tickets_do(): void
    {
        $gate = $this->gate();
        $active = $this->acquire($gate, 'active');
        $gone = $this->acquire($gate, 'gone');
        $this->time += 601;
        $next = $this->acquire($gate, 'next');
        $this->assertSame('waiting', $next['state']);
        $this->assertSame(1, $next['position']);
        fclose($active['permit']);
        $this->assertSame('ready', $this->acquire($gate, 'next', $next['ticket'])['state']);
        $this->assertStringNotContainsString($gone['ticket'], file_get_contents($this->directory.'/queue.json'));
    }

    public function test_ready_reservations_expire_and_can_be_cancelled_only_by_their_owner_and_target(): void
    {
        $gate = $this->gate();
        $reserved = $gate->evaluate('owner', 'POST /form', null);
        $this->assertSame('ready', $reserved['state']);
        $this->assertArrayNotHasKey('permit', $reserved);
        $gate->cancel('attacker', 'POST /form', $reserved['ticket']);
        $gate->cancel('owner', 'POST /other', $reserved['ticket']);
        $next = $this->acquire($gate, 'next');
        $this->assertSame('waiting', $next['state']);
        $gate->cancel('owner', 'POST /form', $reserved['ticket']);
        $reservedNext = $gate->evaluate('next', 'POST /kelas/synthetic', $next['ticket']);
        $this->assertSame('ready', $reservedNext['state']);
        $this->time += 31;
        $this->assertSame('ready', $this->acquire($gate, 'after-expiry')['state']);
    }

    public function test_permit_replay_or_wrong_owner_method_or_path_cannot_consume_the_reservation(): void
    {
        $gate = $this->gate(['capacity' => 8]);
        $ready = $gate->evaluate('owner', 'POST /form', null);
        foreach ([['stranger', 'POST /form'], ['owner', 'GET /form'], ['owner', 'POST /other']] as [$owner, $target]) {
            $attempt = $this->acquire($gate, $owner, $ready['ticket'], $target);
            $this->assertArrayNotHasKey('permit', $attempt);
            $this->assertNotSame($ready['ticket'], $attempt['ticket']);
        }
        $used = $this->acquire($gate, 'owner', $ready['ticket'], 'POST /form');
        $this->assertSame('ready', $used['state']);
        fclose($used['permit']);
        $this->assertArrayNotHasKey('permit', $this->acquire($gate, 'owner', $ready['ticket'], 'POST /form'));
    }

    public function test_queue_is_bounded_and_cancellation_preserves_other_tickets(): void
    {
        $gate = $this->gate(['max_waiting' => 2]);
        $this->acquire($gate, 'active');
        $first = $this->acquire($gate, 'first');
        $second = $this->acquire($gate, 'second');
        $this->assertSame('full', $this->acquire($gate, 'third')['state']);
        $gate->cancel('first', 'POST /kelas/synthetic', $first['ticket']);
        $this->assertSame(2, $this->acquire($gate, 'third')['position']);
        $this->assertSame(1, $this->acquire($gate, 'second', $second['ticket'])['position']);
        $state = file_get_contents($this->directory.'/queue.json');
        $this->assertStringNotContainsString('/kelas/synthetic', $state);
        $this->assertStringNotContainsString('second', $state);
    }

    public function test_nonblocking_lock_and_corrupt_storage_fail_closed(): void
    {
        $gate = $this->gate();
        $active = $this->acquire($gate, 'active');
        $lock = fopen($this->directory.'/queue.lock', 'c+');
        $this->handles[] = $lock;
        flock($lock, LOCK_EX);
        $start = microtime(true);
        $this->assertSame('busy', $this->acquire($gate, 'other')['state']);
        $this->assertLessThan(0.5, microtime(true) - $start);
        fclose($lock);
        file_put_contents($this->directory.'/queue.json', '{broken');
        $this->assertSame('unavailable', $this->acquire($gate, 'other')['state']);
        $this->assertSame('{broken', file_get_contents($this->directory.'/queue.json'));
        $this->assertIsResource($active['permit']);
    }

    public function test_admission_rate_limits_short_requests_in_addition_to_concurrency(): void
    {
        $gate = $this->gate(['starts_per_second' => 1]);
        $first = $gate->evaluate('first', 'GET /', null, true);
        fclose($first['permit']);
        $second = $gate->evaluate('second', 'GET /', null, true);
        $this->assertSame('waiting', $second['state']);
        $this->time += 0.5;
        $this->assertSame('waiting', $gate->evaluate('second', 'GET /', $second['ticket'], true)['state']);
        $this->time += 0.5;
        $next = $gate->evaluate('second', 'GET /', $second['ticket'], true);
        $this->handles[] = $next['permit'];
        $this->assertSame('ready', $next['state']);
    }

    public function test_reducing_capacity_counts_existing_active_slots_until_they_finish(): void
    {
        $gate = $this->gate(['capacity' => 3]);
        $one = $this->acquire($gate, 'one');
        $two = $this->acquire($gate, 'two');
        $three = $this->acquire($gate, 'three');
        $lower = $this->gate(['capacity' => 1]);
        fclose($one['permit']);
        $waiting = $this->acquire($lower, 'four');
        $this->assertSame('waiting', $waiting['state']);
        fclose($two['permit']);
        $this->assertSame('waiting', $this->acquire($lower, 'four', $waiting['ticket'])['state']);
        fclose($three['permit']);
        $this->assertSame('ready', $this->acquire($lower, 'four', $waiting['ticket'])['state']);
    }

    public function test_only_local_return_paths_and_opaque_transport_parameter_are_allowed(): void
    {
        foreach (['https://evil.test/', '//evil.test', '/\\evil.test', "/\nLocation: evil", '/waiting-room.html', '/admission.php'] as $path) {
            $this->assertNull(AdmissionHttp::path($path));
        }
        $this->assertSame('/kelas/demo/l1?a=1&b=2', AdmissionHttp::path('/kelas/demo/l1?a=1&__admission=opaque&b=2'));
    }
}
