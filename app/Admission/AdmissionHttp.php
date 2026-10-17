<?php

namespace App\Admission;

/** Minimal HTTP adapter; never boot Laravel or read participant data here. */
final class AdmissionHttp
{
    public static function settings(): ?array
    {
        $file = dirname(__DIR__, 2).'/storage/framework/admission-config.php';

        return is_file($file) ? require $file : null;
    }

    public static function gate(array $settings): FileAdmissionGate
    {
        return new FileAdmissionGate(dirname(__DIR__, 2).'/storage/framework/admission', $settings);
    }

    public static function path(string $path): ?string
    {
        if (strlen($path) > 4096 || ! str_starts_with($path, '/') || str_starts_with($path, '//')
            || preg_match('/[\\x00-\\x20\\x7f\\\\#]/', $path)) {
            return null;
        }
        $parts = explode('?', $path, 2);
        $decoded = rawurldecode($parts[0]);
        if (str_starts_with($decoded, '//') || preg_match('/[\\x00-\\x20\\x7f\\\\]/', $decoded)
            || preg_match('#^/(admission\\.php|waiting-room\\.html)(/|$)#i', $decoded)) {
            return null;
        }
        $query = array_filter(explode('&', $parts[1] ?? ''), fn ($part) => $part !== '' && urldecode(explode('=', $part, 2)[0]) !== '__admission');

        return $parts[0].($query ? '?'.implode('&', $query) : '');
    }

    public static function owner(): string
    {
        $owner = $_COOKIE['lentera_admission'] ?? '';
        if (! is_string($owner) || ! preg_match('/^[a-f0-9]{64}$/D', $owner)) {
            $owner = bin2hex(random_bytes(32));
            setcookie('lentera_admission', $owner, [
                'expires' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
                'secure' => ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            ]);
        }

        return $owner;
    }

    public static function json(array $reply, int $status): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');
        if (isset($reply['retry_after'])) {
            header('Retry-After: '.$reply['retry_after']);
        }
        echo json_encode($reply, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function enter(): void
    {
        $settings = self::settings();
        // Activation is explicit through the deployment command.
        if (! ($settings['enabled'] ?? false)) {
            return;
        }
        $path = self::path($_SERVER['REQUEST_URI'] ?? '/');
        if ($path === null) {
            self::json(['state' => 'invalid'], 400);
        }
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $ticket = $_SERVER['HTTP_X_LENTERA_TICKET'] ?? $_GET['__admission'] ?? null;
        $ticket = is_string($ticket) && preg_match('/^[a-f0-9]{64}$/D', $ticket) ? $ticket : null;
        $reply = self::gate($settings)->evaluate(self::owner(), $method.' '.$path, $ticket, true);
        header('Cache-Control: private, no-store');
        header('Referrer-Policy: no-referrer');
        if (isset($reply['permit'])) {
            $permit = $reply['permit'];
            register_shutdown_function(static function () use ($permit) {
                if (is_resource($permit)) {
                    fclose($permit);
                }
            });
            // The permit is transport metadata, never business input.
            unset($_GET['__admission']);
            $_SERVER['REQUEST_URI'] = $path;
            $_SERVER['QUERY_STRING'] = explode('?', $path, 2)[1] ?? '';

            return;
        }
        header('X-Lentera-Admission: waiting');
        if (in_array($method, ['GET', 'HEAD'], true) && ! isset($_SERVER['HTTP_X_LENTERA_ASYNC'])
            && ! str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            $fragment = rawurlencode(json_encode(['path' => $path, 'ticket' => $reply['ticket'] ?? null]));
            header('Location: /waiting-room.html#'.$fragment, true, 302);
            exit;
        }
        if (isset($_SERVER['HTTP_X_LENTERA_ASYNC']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            self::json($reply, $reply['state'] === 'unavailable' ? 503 : 429);
        }
        // JavaScript-free POST: never silently redirect or replay a form.
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        header('Retry-After: '.$reply['retry_after']);
        echo '<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Antrean LENTERA</title><h1>LENTERA sedang ramai.</h1><p>Kiriman ini belum diproses. Gunakan tombol Kembali pada browser, periksa isian, lalu coba lagi. Aktifkan JavaScript untuk menunggu tanpa meninggalkan formulir.</p></html>';
        exit;
    }
}
