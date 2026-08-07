<?php

namespace App\Models;

use App\Enums\ApprovalStage;
use App\Enums\Decision;
use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'stage' => ApprovalStage::class,
            'decision' => Decision::class,
            'acted_at' => 'datetime',
        ];
    }

    public function activityRequest()
    {
        return $this->belongsTo(ActivityRequest::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
