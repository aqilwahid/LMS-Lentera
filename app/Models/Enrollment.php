<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['guest_access_hash'];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    protected static function booted(): void
    {
        static::updating(function ($enrollment) {
            if ($enrollment->isDirty(['user_id', 'activity_id', 'institution_id', 'institution_name', 'participant_category', 'joined_session_id', 'guest_access_hash', 'full_name', 'identity_number', 'department', 'faculty', 'phone', 'email'])) {
                throw new \LogicException('Konteks kepesertaan historis tidak boleh diubah.');
            }
        });
    }
}
