<?php

namespace App\Services;

class MaterialUploadLimit
{
    private function bytes(string $value): int
    {
        $value = trim($value);
        if ((float) $value <= 0) {
            return PHP_INT_MAX;
        }
        $factor = match (strtolower(substr($value, -1))) {
            'g' => 1024 ** 3,'m' => 1024 ** 2,'k' => 1024,default => 1
        };

        return (int) ((float) $value * $factor);
    }

    public function kilobytes(): int
    {
        $post = $this->bytes((string) ini_get('post_max_size'));

        return max(1, min((int) config('lentera.material_max_kb'), intdiv($this->bytes((string) ini_get('upload_max_filesize')), 1024), intdiv(max(1024, $post - 65536), 1024)));
    }
}
