<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class L2Attempt extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $hidden = ['instrument_snapshot'];

    protected function casts(): array
    {
        return ['instrument_snapshot' => 'array', 'answers' => 'array', 'revision' => 'integer', 'started_at' => 'datetime', 'submitted_at' => 'datetime', 'score_percent' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::updating(function ($attempt) {
            if ($attempt->getOriginal('submitted_at') || $attempt->isDirty(['l2_assessment_id', 'enrollment_id', 'l2_instrument_id', 'stage', 'instrument_snapshot', 'question_count', 'started_at'])) {
                throw new \LogicException('Konteks attempt dan jawaban L2 final tidak boleh diubah.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Attempt L2 tidak boleh dihapus.'));
    }
}
