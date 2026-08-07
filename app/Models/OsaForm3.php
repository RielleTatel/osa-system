<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OsaForm3 extends Model
{
    protected $table = 'osa_form3s';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'number_of_students' => 'integer',
            'moderator_approved_at' => 'datetime',
        ];
    }

    public function activityRequest()
    {
        return $this->belongsTo(ActivityRequest::class);
    }

    public function complianceItems()
    {
        return $this->hasMany(OsaForm3ComplianceItem::class);
    }

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }
}
