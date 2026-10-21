<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['guest_access_hash'];

    protected function casts(): array
    {
        return ['revision' => 'integer'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function joinedSession()
    {
        return $this->belongsTo(LearningSession::class, 'joined_session_id');
    }

    public function mappedInstitution()
    {
        return $this->belongsTo(Institution::class, 'mapped_institution_id');
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    protected static function booted(): void
    {
        static::updating(function ($enrollment) {
            if ($enrollment->isDirty(['user_id', 'activity_id', 'institution_id', 'institution_name', 'participant_category', 'joined_session_id', 'guest_access_hash'])) {
                throw new \LogicException('Konteks kepesertaan historis tidak boleh diubah.');
            }
        });
    }
}
