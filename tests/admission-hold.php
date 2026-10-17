<?php

// CLI-only synthetic contention fixture for admission-browser.cjs.
use App\Admission\AdmissionHttp;

if (PHP_SAPI !== 'cli') {
    exit(1);
}
require __DIR__.'/../app/Admission/FileAdmissionGate.php';
require __DIR__.'/../app/Admission/AdmissionHttp.php';
$settings = AdmissionHttp::settings();
if (! ($settings['enabled'] ?? false)) {
    fwrite(STDERR, "Enable local admission before this test.\n");
    exit(1);
}
$gate = AdmissionHttp::gate($settings);
$permits = [];
$deadline = microtime(true) + 15;
try {
    for ($i = 0; $i < $settings['capacity']; $i++) {
        $ticket = null;
        $owner = bin2hex(random_bytes(32));
        do {
            $result = $gate->evaluate($owner, 'GET /synthetic-test-holder', $ticket, true);
            $ticket = $result['ticket'] ?? $ticket;
            if (isset($result['permit'])) {
                $permits[] = $result['permit'];
                break;
            }
            if (microtime(true) > $deadline) {
                throw new RuntimeException('Cannot obtain synthetic slots in time.');
            }
            usleep(50000);
        } while (true);
    }
    echo "READY\n";
    flush();
    stream_set_blocking(STDIN, false);
    $deadline = microtime(true) + 45;
    while (microtime(true) < $deadline && ! feof(STDIN)) {
        if (fgets(STDIN) !== false) {
            break;
        }
        usleep(50000);
    }
} finally {
    foreach ($permits as $permit) {
        fclose($permit);
    }
}
