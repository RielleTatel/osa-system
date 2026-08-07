<?php

namespace App\Enums;

enum ChecklistStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case Verified = 'verified';
}
