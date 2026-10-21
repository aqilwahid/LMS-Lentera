<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    protected $guarded = ['id'];

    public function session()
    {
        return $this->belongsTo(LearningSession::class, 'learning_session_id');
    }
}
