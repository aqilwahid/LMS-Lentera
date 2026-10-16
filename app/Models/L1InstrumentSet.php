<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class L1InstrumentSet extends Model
{
    protected $guarded = ['id'];

    public function instruments()
    {
        return $this->hasMany(L1Instrument::class);
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Identitas seri instrumen tidak boleh diubah.'));
        static::deleting(fn () => throw new \LogicException('Seri instrumen tidak boleh dihapus.'));
    }
}
