<?php

namespace App\Enums;

enum ActivityStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case ModeratorEndorsed = 'moderator_endorsed';
    case OsaReviewing = 'osa_reviewing';
    case DocsComplete = 'docs_complete';
    case AwaitingPhysical = 'awaiting_physical';
    case Approved = 'approved';
    case Denied = 'denied';
    case RevisionNeeded = 'revision_needed';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
