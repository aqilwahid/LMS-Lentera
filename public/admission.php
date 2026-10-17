<?php

use App\Admission\AdmissionHttp;

require_once __DIR__.'/../app/Admission/FileAdmissionGate.php';
require_once __DIR__.'/../app/Admission/AdmissionHttp.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    AdmissionHttp::json(['state' => 'invalid'], 405);
}
if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site') {
    AdmissionHttp::json(['state' => 'invalid'], 403);
}
if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') {
    AdmissionHttp::json(['state' => 'invalid'], 415);
}
// SameSite alone also allows sibling sites; do not accept their simple POSTs.
$origin = $_SERVER['HTTP_ORIGIN'] ?? null;
$scheme = ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
if ($origin !== null && ! hash_equals(strtolower($scheme.'://'.($_SERVER['HTTP_HOST'] ?? '')), strtolower($origin))) {
    AdmissionHttp::json(['state' => 'invalid'], 403);
}
$body = file_get_contents('php://input', false, null, 0, 8193);
$data = strlen($body) <= 8192 ? json_decode($body, true) : null;
$path = is_array($data) && is_string($data['path'] ?? null) ? AdmissionHttp::path($data['path']) : null;
$method = $data['method'] ?? null;
$ticket = $data['ticket'] ?? null;
if ($path === null || ! in_array($method, ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'], true)
    || ($ticket !== null && (! is_string($ticket) || ! preg_match('/^[a-f0-9]{64}$/D', $ticket)))) {
    AdmissionHttp::json(['state' => 'invalid'], 400);
}
$settings = AdmissionHttp::settings();
if (! ($settings['enabled'] ?? false)) {
    AdmissionHttp::json(['state' => 'disabled', 'retry_after' => 0], 200);
}
$gate = AdmissionHttp::gate($settings);
$reply = ($data['action'] ?? '') === 'cancel'
    ? $gate->cancel(AdmissionHttp::owner(), $method.' '.$path, $ticket)
    : $gate->evaluate(AdmissionHttp::owner(), $method.' '.$path, $ticket);
AdmissionHttp::json($reply, match ($reply['state']) {
    'ready', 'cancelled' => 200,
    'waiting' => 202,
    'full' => 429,
    default => 503,
});
