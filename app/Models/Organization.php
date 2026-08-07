<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'accreditation_status'];

    public function officers()
    {
        return $this->hasMany(User::class);
    }

    public function moderators()
    {
        return $this->belongsToMany(User::class, 'organization_moderator');
    }

    public function activityRequests()
    {
        return $this->hasMany(ActivityRequest::class);
    }

    public function isAccredited(): bool
    {
        return $this->accreditation_status === 'accredited';
    }
}
