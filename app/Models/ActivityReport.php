<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ActivityReport extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['filters' => 'array', 'filter_labels' => 'array', 'rows' => 'array'];
    }

    public function filePath(string $format): string
    {
        return "activity-reports/{$this->id}/report.{$format}";
    }
}
