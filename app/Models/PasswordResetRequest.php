<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'fulfilled_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fulfilledBy()
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
