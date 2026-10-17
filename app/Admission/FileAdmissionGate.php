<?php

namespace App\Admission;

use Closure;
use RuntimeException;
use Throwable;

/** No Composer, session or database: this also runs before Laravel boots. */
final class FileAdmissionGate
{
    private array $config;

    public function __construct(private string $directory, array $config, private ?Closure $clock = null)
    {
        $this->config = array_replace([
            'capacity' => 8, 'max_waiting' => 2000, 'ticket_ttl' => 180,
            'reservation_ttl' => 30, 'poll_seconds' => 20, 'starts_per_second' => 8,
        ], $config);
        foreach (['capacity' => [1, 32], 'max_waiting' => [1, 5000], 'ticket_ttl' => [60, 600],
            'reservation_ttl' => [10, 60], 'poll_seconds' => [5, 60], 'starts_per_second' => [1, 100]] as $key => [$min, $max]) {
            if (! is_int($this->config[$key]) || $this->config[$key] < $min || $this->config[$key] > $max) {
                throw new RuntimeException('Invalid admission setting: '.$key);
            }
        }
    }

    /**
     * A ready consumed permit is an OS lock resource. Close it ONLY after the
     * entire request (including streaming/termination) finishes. No active TTL.
     */
    public function evaluate(string $owner, string $target, ?string $ticket, bool $consume = false): array
    {
        return $this->locked(function (array &$state, array &$slots) use ($owner, $target, $ticket, $consume) {
            $now = $this->now();
            $owner = hash('sha256', $owner);
            $target = hash('sha256', $target);
            $existing = $ticket !== null && isset($state['tickets'][$ticket])
                && hash_equals($state['tickets'][$ticket]['owner'], $owner)
                && hash_equals($state['tickets'][$ticket]['target'], $target);
            $stalePermit = $consume && $ticket !== null && ! $existing;

            if (! $existing) {
                $owned = count(array_filter($state['tickets'], fn ($row) => $row['owner'] === $owner));
                if (count($state['tickets']) >= $this->config['max_waiting'] || $owned >= 6) {
                    return $this->reply('full');
                }
                $ticket = bin2hex(random_bytes(32));
                $state['tickets'][$ticket] = [
                    'owner' => $owner, 'target' => $target, 'expires' => $now + $this->config['ticket_ttl'],
                    'slot' => null, 'reserved_until' => null,
                ];
            }
            $state['tickets'][$ticket]['expires'] = $now + $this->config['ticket_ttl'];
            $reserved = array_column($state['tickets'], 'slot');
            $free = array_values(array_diff(array_keys($slots), array_filter($reserved, fn ($slot) => $slot !== null)));
            // FIFO reservations; newcomers cannot jump over waiting tickets.
            foreach ($state['tickets'] as &$row) {
                if ($row['slot'] === null && $free && $state['tokens'] >= 1) {
                    $row['slot'] = array_shift($free);
                    $row['reserved_until'] = $now + $this->config['reservation_ttl'];
                    $state['tokens'] -= 1;
                }
            }
            unset($row);
            $row = $state['tickets'][$ticket];
            $position = array_search($ticket, array_keys($state['tickets']), true) + 1;
            if ($row['slot'] !== null && isset($slots[$row['slot']]) && ! $stalePermit) {
                $reply = $this->reply('ready', $ticket, 0);
                if ($consume) {
                    $reply['permit'] = $slots[$row['slot']];
                    unset($slots[$row['slot']], $state['tickets'][$ticket]);
                }

                return $reply;
            }

            return $this->reply('waiting', $ticket, $position);
        });
    }

    public function cancel(string $owner, string $target, ?string $ticket): array
    {
        return $this->locked(function (array &$state) use ($owner, $target, $ticket) {
            $row = $state['tickets'][$ticket ?? ''] ?? null;
            if ($row && hash_equals($row['owner'], hash('sha256', $owner)) && hash_equals($row['target'], hash('sha256', $target))) {
                unset($state['tickets'][$ticket]);
            }

            return $this->reply('cancelled');
        });
    }

    private function reply(string $status, ?string $ticket = null, ?int $position = null): array
    {
        $delay = $position === null ? $this->config['poll_seconds']
            : min($this->config['poll_seconds'], max(2, (int) ceil($position / $this->config['capacity']) * 2));

        return array_filter(['state' => $status, 'ticket' => $ticket, 'position' => $position, 'retry_after' => $delay], fn ($v) => $v !== null);
    }

    private function locked(callable $callback): array
    {
        $lock = null;
        $slots = [];
        $reply = null;
        try {
            if (! is_dir($this->directory) && ! @mkdir($this->directory, 0700, true) && ! is_dir($this->directory)) {
                throw new RuntimeException('Admission directory unavailable.');
            }
            $lock = @fopen($this->directory.'/queue.lock', 'c+');
            if (! $lock) {
                throw new RuntimeException('Admission lock unavailable.');
            }
            if (! flock($lock, LOCK_EX | LOCK_NB)) {
                return $this->reply('busy');
            }
            $path = $this->directory.'/queue.json';
            $now = $this->now();
            if (is_file($path) && filesize($path) > 2097152) {
                throw new RuntimeException('Admission state exceeds its size limit.');
            }
            $state = is_file($path) ? json_decode(file_get_contents($path), true, 16, JSON_THROW_ON_ERROR)
                : ['tickets' => [], 'tokens' => $this->config['capacity'], 'updated_at' => $now];
            if (! is_array($state) || ! isset($state['tickets'], $state['tokens'], $state['updated_at']) || ! is_array($state['tickets'])
                || ! is_numeric($state['tokens']) || ! is_numeric($state['updated_at']) || count($state['tickets']) > 5000) {
                throw new RuntimeException('Invalid admission state.');
            }
            $state['tokens'] = min($this->config['capacity'], $state['tokens'] + max(0, $now - $state['updated_at']) * $this->config['starts_per_second']);
            $state['updated_at'] = $now;
            foreach ($state['tickets'] as $key => $row) {
                if (! is_array($row) || ! preg_match('/^[a-f0-9]{64}$/D', (string) $key)
                    || ! isset($row['owner'], $row['target'], $row['expires']) || ! is_numeric($row['expires'])
                    || ! is_string($row['owner']) || ! is_string($row['target'])
                    || ! array_key_exists('slot', $row) || ! array_key_exists('reserved_until', $row)
                    || ($row['slot'] !== null && (! is_int($row['slot']) || $row['slot'] < 0 || $row['slot'] > 31 || ! is_numeric($row['reserved_until'])))) {
                    throw new RuntimeException('Invalid admission ticket state.');
                }
                if ($row['expires'] <= $now || ($row['reserved_until'] !== null && $row['reserved_until'] <= $now)
                    || ($row['slot'] !== null && $row['slot'] >= $this->config['capacity'])) {
                    unset($state['tickets'][$key]);
                }
            }
            // Probe all slots, including old slots when capacity is lowered.
            // Keeping a live process's lock prevents reclaiming its capacity.
            $occupied = 0;
            for ($i = 0; $i < 32; $i++) {
                $slotPath = $this->directory.'/slot-'.$i.'.lock';
                if ($i >= $this->config['capacity'] && ! is_file($slotPath)) {
                    continue;
                }
                $handle = @fopen($slotPath, 'c+');
                if (! $handle) {
                    throw new RuntimeException('Admission slot unavailable.');
                }
                if (flock($handle, LOCK_EX | LOCK_NB)) {
                    if ($i < $this->config['capacity']) {
                        $slots[$i] = $handle;
                    } else {
                        fclose($handle);
                    }
                } else {
                    $occupied++;
                    fclose($handle);
                }
            }
            while (count($slots) > max(0, $this->config['capacity'] - $occupied)) {
                fclose(array_pop($slots));
            }
            $reply = $callback($state, $slots);
            $encoded = json_encode($state, JSON_THROW_ON_ERROR);
            // Atomic replacement under a separate lock: a partial state fails
            // closed, never silently resets the admission cap.
            if (file_put_contents($path.'.tmp', $encoded) !== strlen($encoded) || ! rename($path.'.tmp', $path)) {
                throw new RuntimeException('Admission state cannot be saved.');
            }

            return $reply;
        } catch (Throwable $exception) {
            if (is_resource($reply['permit'] ?? null)) {
                fclose($reply['permit']);
            }
            // Do not log request paths, cookies or form data.
            error_log('LENTERA admission unavailable: '.$exception->getMessage());

            return $this->reply('unavailable');
        } finally {
            foreach ($slots as $slot) {
                fclose($slot);
            }
            if (is_resource($lock)) {
                fclose($lock);
            }
        }
    }

    private function now(): float
    {
        return $this->clock ? ($this->clock)() : microtime(true);
    }
}
