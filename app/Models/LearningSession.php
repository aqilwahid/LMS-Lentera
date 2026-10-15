<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningSession extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'l1_opens_at' => 'datetime', 'l1_closes_at' => 'datetime'];
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
            if ($session->isDirty(['activity_id', 'l1_instrument_id', 'speaker_name']) && L1Response::where('learning_session_id', $session->id)->exists()) {
                throw new \LogicException('Konteks sesi dengan respons final tidak boleh diubah; buat sesi baru.');
            }
        });
    }
}
