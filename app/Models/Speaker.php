<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Speaker extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'revision' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(function ($record) {
            if ($record->isDirty('office_id')) {
                throw new \LogicException('Master tidak boleh dipindahkan antar KPwBI.');
            }
        });
    }
}
