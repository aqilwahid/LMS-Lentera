<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'is_demo' => 'boolean'];
    }

    public function sessions()
    {
        return $this->hasMany(LearningSession::class);
    }
}
