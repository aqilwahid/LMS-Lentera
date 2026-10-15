<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class L1Instrument extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['questions' => 'array', 'published_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function ($instrument) {
            if ($instrument->getOriginal('published_at')) {
                throw new \LogicException('Instrumen terbit tidak boleh diubah; buat versi baru.');
            }
        });
        static::deleting(function ($instrument) {
            if ($instrument->published_at) {
                throw new \LogicException('Instrumen terbit tidak boleh dihapus.');
            }
        });
    }
}
