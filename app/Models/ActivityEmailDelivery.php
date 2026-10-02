<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityEmailDelivery extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'summary' => 'array',
            'last_attempt_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }
}
