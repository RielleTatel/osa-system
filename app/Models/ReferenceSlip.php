<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferenceSlip extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    public function activityRequest()
    {
        return $this->belongsTo(ActivityRequest::class);
    }
}
