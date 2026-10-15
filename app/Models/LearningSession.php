<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningSession extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'l1_opens_at' => 'datetime', 'l1_closes_at' => 'datetime', 'join_opens_at' => 'datetime', 'join_closes_at' => 'datetime', 'join_enabled' => 'boolean', 'revision' => 'integer'];
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function instrument()
    {
        return $this->belongsTo(L1Instrument::class, 'l1_instrument_id');
    }

    protected static function booted(): void
    {
        static::updating(function ($session) {
            if ($session->isDirty(['activity_id', 'title', 'l1_instrument_id', 'speaker_name', 'speaker_id'])
                && (L1Response::where('learning_session_id', $session->id)->exists() || Enrollment::where('joined_session_id', $session->id)->exists())) {
                throw new \LogicException('Konteks sesi dengan peserta atau respons final tidak boleh diubah; buat sesi baru.');
            }
        });
    }
}
