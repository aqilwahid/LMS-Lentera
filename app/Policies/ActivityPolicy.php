<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function update(User $user, Activity $activity): bool
    {
        return $user->is_active && $user->role === 'bi_admin'
            && $user->office_id !== null && $user->office_id === $activity->office_id;
    }

    public function view(User $user, Activity $activity): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return match ($user->role) {
            'bi_admin' => $user->office_id !== null && $user->office_id === $activity->office_id,
            'participant' => $user->enrollments()->where('activity_id', $activity->id)->where('status', 'active')->exists(),
            default => false,
        };
    }
}
