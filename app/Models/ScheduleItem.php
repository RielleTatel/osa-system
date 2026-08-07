<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleItem extends Model
{
    protected $guarded = [];

    public function activityRequest()
    {
        return $this->belongsTo(ActivityRequest::class);
    }
}
