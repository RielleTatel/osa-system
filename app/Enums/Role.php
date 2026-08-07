<?php

namespace App\Enums;

enum Role: string
{
    case OrgOfficer = 'org_officer';
    case Moderator = 'moderator';
    case OsaAdmin = 'osa_admin';
    case OsaDirector = 'osa_director';

    public function label(): string
    {
        return match ($this) {
            self::OrgOfficer => 'Organization Officer',
            self::Moderator => 'Moderator',
            self::OsaAdmin => 'OSA Admin',
            self::OsaDirector => 'OSA Director',
        };
    }
}
