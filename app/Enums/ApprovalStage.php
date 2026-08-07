<?php

namespace App\Enums;

enum ApprovalStage: string
{
    case ModeratorEndorsement = 'moderator_endorsement';
    case OsaReview = 'osa_review';
    case OsaDirectorNotation = 'osa_director_notation';

    public function label(): string
    {
        return match ($this) {
            self::ModeratorEndorsement => 'Moderator endorsement',
            self::OsaReview => 'OSA review',
            self::OsaDirectorNotation => 'OSA Director notation',
        };
    }
}
