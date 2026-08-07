<?php

namespace App\Enums;

enum DocumentType: string
{
    case ModeratorId = 'moderator_id';
    case DeanId = 'dean_id';
    case ParentId = 'parent_id';
    case StudentId = 'student_id';
    case ParticipantListFile = 'participant_list_file';
    case ScheduleFile = 'schedule_file';
}
