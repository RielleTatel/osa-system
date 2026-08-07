<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OsaForm3ComplianceItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'compliance' => 'boolean',
        ];
    }

    public function osaForm3()
    {
        return $this->belongsTo(OsaForm3::class);
    }
}
