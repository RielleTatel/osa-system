<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ActivityStatus::class,
            'date_start' => 'date',
            'date_end' => 'date',
            'submitted_at' => 'datetime',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function scheduleItems()
    {
        return $this->hasMany(ScheduleItem::class);
    }

    public function documentUploads()
    {
        return $this->hasMany(DocumentUpload::class);
    }

    public function checklistItems()
    {
        return $this->hasMany(ChecklistItem::class);
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class);
    }

    public function osaForm3()
    {
        return $this->hasOne(OsaForm3::class);
    }

    public function referenceSlip()
    {
        return $this->hasOne(ReferenceSlip::class);
    }

    public function scopeStatus($query, ActivityStatus $status)
    {
        return $query->where('status', $status);
    }

    public function isOffCampus(): bool
    {
        return $this->activity_type === 'off_campus';
    }
}
