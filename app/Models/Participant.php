<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    protected $guarded = [];

    public function activityRequest()
    {
        return $this->belongsTo(ActivityRequest::class);
    }
}
