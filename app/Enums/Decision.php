<?php

namespace App\Enums;

enum Decision: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
    case RevisionRequested = 'revision_requested';

    public function label(): string
    {
        return match ($this) {
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::RevisionRequested => 'Revision requested',
        };
    }
}
