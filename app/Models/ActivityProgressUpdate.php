<?php

namespace App\Models;

use App\Enums\ActivityProgress;
use Illuminate\Database\Eloquent\Model;

class ActivityProgressUpdate extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['old_progress' => ActivityProgress::class, 'new_progress' => ActivityProgress::class];
    }
}
