<?php

/**
 * Isolated, multiprocess gate check: php tests/admission-concurrency.php
 * Uses synthetic tickets and a new OS-temp directory; never boots Laravel or sends HTTP.
 * This checks admission correctness, not hosting capacity or application throughput.
 */

use App\Admission\FileAdmissionGate;

require __DIR__.'/../app/Admission/FileAdmissionGate.php';

function admissionCheck(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function admissionConfig(int $capacity): array
{
    return [
        'capacity' => $capacity,
        'max_waiting' => 64,
        'ticket_ttl' => 180,
        'reservation_ttl' => 30,
        'poll_seconds' => 5,
        'starts_per_second' => 100,
    ];
}

function admissionTempRoot(string $directory): string
{
    $root = realpath($directory);
    $temporary = realpath(sys_get_temp_dir());
    admissionCheck($root !== false && $temporary !== false, 'Temporary directory is missing.');
    admissionCheck(dirname($root) === $temporary && preg_match('/^lentera-admission-[a-f0-9]{16}$/D', basename($root)) === 1,
        'Refusing to operate outside the dedicated admission test directory.');

    return $root;
}

function admissionCounter(string $root, int $change, bool $completed = false): array
{
    $handle = fopen($root.'/counter.json', 'c+');
    admissionCheck(is_resource($handle), 'Cannot open the test overlap counter.');
    try {
        admissionCheck(flock($handle, LOCK_EX), 'Cannot lock the test overlap counter.');
        $state = json_decode(stream_get_contents($handle), true, 512, JSON_THROW_ON_ERROR);
        $state['active'] += $change;
        $state['peak'] = max($state['peak'], $state['active']);
        $state['completed'] += $completed ? 1 : 0;
        admissionCheck($state['active'] >= 0, 'Test overlap counter became negative.');
        rewind($handle);
        admissionCheck(ftruncate($handle, 0), 'Cannot truncate the test overlap counter.');
        admissionCheck(fwrite($handle, json_encode($state, JSON_THROW_ON_ERROR)) !== false, 'Cannot write test counter.');
        fflush($handle);

        return $state;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function admissionAcquire(FileAdmissionGate $gate, string $owner, string $target, float $timeout = 25, ?string $ticket = null): array
{
    $deadline = microtime(true) + $timeout;
    do {
        $result = $gate->evaluate($owner, $target, $ticket, true);
        $ticket = $result['ticket'] ?? $ticket;
        if (($result['state'] ?? null) === 'ready') {
            admissionCheck(is_resource($result['permit'] ?? null), 'A consumed admission must hold a live flock resource.');

            return $result;
        }
        admissionCheck(in_array($result['state'] ?? null, ['waiting', 'busy'], true),
            'Unexpected admission state: '.($result['state'] ?? 'missing'));
        // Intentionally accelerated local contention test, not the browser polling policy.
        usleep(random_int(10000, 25000));
    } while (microtime(true) < $deadline);

    throw new RuntimeException('Timed out waiting for a synthetic admission.');
}

function admissionWorker(string $directory, int $worker): void
{
    $root = admissionTempRoot($directory);
    $deadline = microtime(true) + 15;
    while (! is_file($root.'/start.flag')) {
        admissionCheck(microtime(true) < $deadline, 'Timed out at the subprocess start barrier.');
        usleep(10000);
    }
    $gate = new FileAdmissionGate($root.'/gate', admissionConfig(3));
    $owner = bin2hex(random_bytes(32));
    for ($iteration = 0; $iteration < 4; $iteration++) {
        $result = admissionAcquire($gate, $owner, hash('sha256', 'synthetic:'.$worker.':'.$iteration));
        $counted = false;
        try {
            $state = admissionCounter($root, 1);
            $counted = true;
            admissionCheck($state['active'] <= 3, 'Admission capacity exceeded across PHP processes.');
            usleep(random_int(140000, 220000));
        } finally {
            if ($counted) {
                admissionCounter($root, -1, true);
            }
            fclose($result['permit']);
        }
    }
}

function admissionCrashWorker(string $directory): void
{
    $root = admissionTempRoot($directory);
    $gate = new FileAdmissionGate($root.'/crash-gate', admissionConfig(1));
    $result = admissionAcquire($gate, bin2hex(random_bytes(32)), hash('sha256', 'synthetic:crash-holder'));
    admissionCheck(file_put_contents($root.'/crash-held.flag', 'held') !== false, 'Cannot signal the crash-test holder.');
    // Parent terminates this process abruptly. No TTL or graceful release should be needed.
    $deadline = microtime(true) + 20;
    while (microtime(true) < $deadline) {
        usleep(100000);
    }
    fclose($result['permit']);
    throw new RuntimeException('Parent failed to terminate the crash-test holder in time.');
}

function admissionSpawn(string $root, string $mode, string $id): array
{
    $log = $root.'/'.$mode.'-'.$id.'.log';
    $process = proc_open([PHP_BINARY, __FILE__, '--'.$mode, $root, $id], [
        0 => ['pipe', 'r'],
        1 => ['file', $log, 'w'],
        2 => ['redirect', 1],
    ], $pipes, null, null, ['bypass_shell' => true]);
    admissionCheck(is_resource($process), 'Cannot start the PHP test subprocess.');
    fclose($pipes[0]);

    return ['process' => $process, 'log' => $log];
}

function admissionWait(array $child, float $deadline, bool $expectSuccess = true): void
{
    do {
        $status = proc_get_status($child['process']);
        if (! $status['running']) {
            $closed = proc_close($child['process']);
            $exit = $status['exitcode'] >= 0 ? $status['exitcode'] : $closed;
            admissionCheck(! $expectSuccess || $exit === 0, 'PHP subprocess failed: '.file_get_contents($child['log']));

            return;
        }
        admissionCheck(microtime(true) < $deadline, 'Timed out waiting for a PHP subprocess.');
        usleep(20000);
    } while (true);
}

function admissionCleanup(string $directory): void
{
    $root = admissionTempRoot($directory);
    $entries = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($entries as $entry) {
        // RecursiveDirectoryIterator does not follow symlinks; remove links themselves.
        if ($entry->isLink() || ! $entry->isDir()) {
            admissionCheck(unlink($entry->getPathname()), 'Cannot remove a temporary test file.');
        } else {
            admissionCheck(rmdir($entry->getPathname()), 'Cannot remove a temporary test subdirectory.');
        }
    }
    admissionCheck(rmdir($root), 'Cannot remove the temporary test directory.');
}

if (($argv[1] ?? '') === '--worker' || ($argv[1] ?? '') === '--crash') {
    try {
        if ($argv[1] === '--worker') {
            admissionWorker($argv[2] ?? '', (int) ($argv[3] ?? 0));
        } else {
            admissionCrashWorker($argv[2] ?? '');
        }
        exit(0);
    } catch (Throwable $exception) {
        fwrite(STDERR, $exception->getMessage().PHP_EOL);
        exit(1);
    }
}

$root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'lentera-admission-'.bin2hex(random_bytes(8));
$children = [];
$failure = null;
try {
    admissionCheck(mkdir($root, 0700), 'Cannot create temporary test directory.');
    $root = admissionTempRoot($root);
    admissionCheck(mkdir($root.'/gate', 0700) && mkdir($root.'/crash-gate', 0700), 'Cannot create isolated gate directories.');
    file_put_contents($root.'/counter.json', json_encode(['active' => 0, 'peak' => 0, 'completed' => 0], JSON_THROW_ON_ERROR));
    for ($worker = 0; $worker < 12; $worker++) {
        $children[] = admissionSpawn($root, 'worker', (string) $worker);
    }
    admissionCheck(touch($root.'/start.flag'), 'Cannot release subprocess start barrier.');
    $deadline = microtime(true) + 35;
    foreach ($children as $child) {
        admissionWait($child, $deadline);
    }
    $state = admissionCounter($root, 0);
    admissionCheck($state['active'] === 0 && $state['completed'] === 48, 'Some synthetic requests did not finish or release their slots.');
    admissionCheck($state['peak'] >= 2 && $state['peak'] <= 3, 'Concurrency was either absent or exceeded the configured capacity.');

    $crash = admissionSpawn($root, 'crash', 'holder');
    $children[] = $crash;
    $deadline = microtime(true) + 10;
    while (! is_file($root.'/crash-held.flag')) {
        admissionCheck(proc_get_status($crash['process'])['running'], 'Crash-test holder stopped before acquiring a slot.');
        admissionCheck(microtime(true) < $deadline, 'Crash-test holder did not acquire a slot.');
        usleep(20000);
    }
    $gate = new FileAdmissionGate($root.'/crash-gate', admissionConfig(1));
    $owner = bin2hex(random_bytes(32));
    $target = hash('sha256', 'synthetic:after-crash');
    $queued = $gate->evaluate($owner, $target, null, true);
    admissionCheck(($queued['state'] ?? null) === 'waiting', 'A second request entered while the crash-test holder was active.');
    admissionCheck(is_string($queued['ticket'] ?? null), 'The blocked crash-test request did not receive a ticket.');
    admissionCheck(proc_terminate($crash['process'], 9), 'Cannot terminate crash-test holder.');
    admissionWait($crash, microtime(true) + 5, false);
    $started = microtime(true);
    $afterCrash = admissionAcquire($gate, $owner, $target, 4, $queued['ticket']);
    fclose($afterCrash['permit']);
    $elapsed = microtime(true) - $started;
    admissionCheck($elapsed < 4, 'A crashed holder retained capacity until a lease expired.');
    fwrite(STDOUT, 'PASS: 48 admissions across 12 PHP processes; peak '.$state['peak'].'/3; crashed holder released immediately.'.PHP_EOL);
    fwrite(STDOUT, 'This is a local gate correctness check, not a 1,500-user hosting load test.'.PHP_EOL);
} catch (Throwable $exception) {
    $failure = $exception;
} finally {
    foreach ($children as $child) {
        if (! is_resource($child['process'])) {
            continue;
        }
        if (proc_get_status($child['process'])['running']) {
            proc_terminate($child['process'], 9);
        }
        try {
            admissionWait($child, microtime(true) + 5, false);
        } catch (Throwable $exception) {
            $failure ??= $exception;
        }
    }
    if (is_dir($root)) {
        try {
            admissionCleanup($root);
        } catch (Throwable $exception) {
            $failure ??= $exception;
        }
    }
}
if ($failure) {
    fwrite(STDERR, 'FAIL: '.$failure->getMessage().PHP_EOL);
    exit(1);
}
