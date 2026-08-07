<?php

namespace App\Models;

use App\Enums\ChecklistStatus;
use Illuminate\Database\Eloquent\Model;

class ChecklistItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_physical' => 'boolean',
            'status' => ChecklistStatus::class,
        ];
    }

    public function activityRequest()
    {
        return $this->belongsTo(ActivityRequest::class);
    }
}
