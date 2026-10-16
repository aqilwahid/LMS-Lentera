<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class L2Assessment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['pre_opens_at' => 'datetime', 'pre_closes_at' => 'datetime', 'post_opens_at' => 'datetime', 'post_closes_at' => 'datetime',
            'enabled' => 'boolean', 'require_pretest' => 'boolean', 'results_visible' => 'boolean', 'revision' => 'integer'];
    }

    public function session()
    {
        return $this->belongsTo(LearningSession::class, 'learning_session_id');
    }

    public function instrument()
    {
        return $this->belongsTo(L2Instrument::class, 'l2_instrument_id');
    }

    public function isOpen(string $stage): bool
    {
        return $this->enabled && now()->betweenIncluded($this->{$stage.'_opens_at'}, $this->{$stage.'_closes_at'});
    }

    protected static function booted(): void
    {
        static::updating(function ($assessment) {
            if ($assessment->isDirty('learning_session_id') || ($assessment->isDirty(['l2_instrument_id', 'require_pretest'])
                && L2Attempt::where('l2_assessment_id', $assessment->id)->exists())) {
                throw new \LogicException('Konteks L2 yang sudah dimulai tidak boleh diubah.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Penetapan L2 tidak boleh dihapus.'));
    }
}
