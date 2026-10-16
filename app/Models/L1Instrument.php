<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class L1Instrument extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['questions' => 'array', 'published_at' => 'datetime', 'revision' => 'integer'];
    }

    public function set()
    {
        return $this->belongsTo(L1InstrumentSet::class, 'l1_instrument_set_id');
    }

    public function scopeAvailableToOffice($query, int $officeId)
    {
        return $query->where(fn ($q) => $q->whereNull('l1_instrument_set_id')
            ->orWhereHas('set', fn ($set) => $set->where('office_id', $officeId)));
    }

    public function hasValidQuestions(): bool
    {
        return is_array($this->questions) && array_keys($this->questions) === [0, 1, 2, 3, 4]
            && collect($this->questions)->every(fn ($question) => is_string($question) && trim($question) !== '' && mb_strlen($question) <= 2000);
    }

    protected static function booted(): void
    {
        static::updating(function ($instrument) {
            if ($instrument->getOriginal('published_at')) {
                throw new \LogicException('Instrumen terbit tidak boleh diubah; buat versi baru.');
            }
            if ($instrument->isDirty(['code', 'version', 'l1_instrument_set_id', 'creation_key', 'creation_hash'])) {
                throw new \LogicException('Identitas dan asal versi instrumen tidak boleh diubah.');
            }
        });
        static::deleting(function ($instrument) {
            if ($instrument->published_at) {
                throw new \LogicException('Instrumen terbit tidak boleh dihapus.');
            }
        });
    }
}
