<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean', 'revision' => 'integer', 'current_version_id' => 'integer', 'learning_session_id' => 'integer'];
    }

    public function session()
    {
        return $this->belongsTo(LearningSession::class, 'learning_session_id');
    }

    public function versions()
    {
        return $this->hasMany(MaterialVersion::class);
    }

    public function currentVersion()
    {
        return $this->belongsTo(MaterialVersion::class, 'current_version_id');
    }
}
