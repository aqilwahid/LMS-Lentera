<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class L1Response extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['answers' => 'array', 'instrument_snapshot' => 'array', 'submitted_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Respons final tidak boleh diubah.'));
        static::deleting(fn () => throw new \LogicException('Respons final tidak boleh dihapus.'));
    }
}
