<?php

namespace App\Enums;

enum ActivityProgress: string
{
    case NeedsConfirmation = 'needs_confirmation';
    case Pending = 'pending';
    case Ongoing = 'ongoing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
