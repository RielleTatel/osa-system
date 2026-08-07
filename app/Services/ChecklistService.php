<?php

namespace App\Services;

use App\Enums\ActivityStatus;
use App\Enums\ChecklistStatus;
use App\Enums\DocumentType;
use App\Enums\Role;
use App\Models\ActivityRequest;
use App\Models\ChecklistItem;
use App\Models\User;
use App\Notifications\PacketReadyForPhysicalStage;
use App\Notifications\StatusChanged;
use Illuminate\Support\Facades\Notification;

class ChecklistService
{
    public const ITEM_PARTICIPANT_LIST = 'List of Participants';

    public const ITEM_SCHEDULE = 'Itinerary / Schedule of Activities';

    public const ITEM_MODERATOR_ID = 'ID Photocopy — Moderator';

    public const ITEM_DEAN_ID = 'ID Photocopy — Dean/Head of Office';

    public const ITEM_STUDENT_ID = 'ID Photocopy — Student';

    public const ITEM_PARENT_ID = 'ID Photocopy — Parent/Guardian';

    public const ITEM_OSA_FORM_3 = 'OSA Form 3 (digital form)';

    /**
     * The digital-upload checklist items shared by both activity types,
     * derived from FORM A1 (docs/*-campus forms/FORM A1.md).
     *
     * @return array<string, bool> item_name => is_physical
     */
    public static function template(string $activityType): array
    {
        $digital = [
            self::ITEM_PARTICIPANT_LIST => false,
            self::ITEM_SCHEDULE => false,
            self::ITEM_MODERATOR_ID => false,
            self::ITEM_DEAN_ID => false,
            self::ITEM_STUDENT_ID => false,
            self::ITEM_PARENT_ID => false,
        ];

        return $activityType === 'off_campus'
            ? $digital + [
                self::ITEM_OSA_FORM_3 => false,
                'Blue Form — FORM A2.2 + Certificate of Compliance (wet signatures, notarized)' => true,
                "Parent's Consent (3 notarized copies, wet signature)" => true,
                'Medical Certificate (infirmary clearance, if strenuous/out-of-town)' => true,
            ]
            : $digital + [
                'Pink Form — FORM A1.1 (wet signatures)' => true,
                "Parent's Consent (1 copy, wet signature)" => true,
                'Medical Certificate (infirmary clearance, if strenuous)' => true,
            ];
    }

    public function seedFor(ActivityRequest $request): void
    {
        foreach (self::template($request->activity_type) as $name => $physical) {
            $request->checklistItems()->firstOrCreate(
                ['item_name' => $name],
                ['is_physical' => $physical, 'status' => ChecklistStatus::Pending],
            );
        }
    }

    public function isComplete(ActivityRequest $request): bool
    {
        return ! $request->checklistItems()
            ->where('is_physical', false)
            ->where('status', ChecklistStatus::Pending)
            ->exists();
    }

    public function markSubmitted(ActivityRequest $request, string $itemName): void
    {
        $request->checklistItems()
            ->where('item_name', $itemName)
            ->where('status', ChecklistStatus::Pending)
            ->update(['status' => ChecklistStatus::Submitted]);
    }

    public function verify(ChecklistItem $item, ?string $notes = null): void
    {
        $item->update([
            'status' => ChecklistStatus::Verified,
            'notes' => $notes ?? $item->notes,
        ]);

        $request = $item->activityRequest;

        if ($request->status === ActivityStatus::OsaReviewing && $this->isComplete($request)) {
            $request->update(['status' => ActivityStatus::DocsComplete]);

            Notification::send(
                User::where('role', Role::OsaAdmin)->get(),
                new PacketReadyForPhysicalStage($request),
            );
            Notification::send($request->organization->officers, new StatusChanged($request));
        }
    }

    public function itemNameFor(DocumentType $type): string
    {
        return match ($type) {
            DocumentType::ParticipantListFile => self::ITEM_PARTICIPANT_LIST,
            DocumentType::ScheduleFile => self::ITEM_SCHEDULE,
            DocumentType::ModeratorId => self::ITEM_MODERATOR_ID,
            DocumentType::DeanId => self::ITEM_DEAN_ID,
            DocumentType::StudentId => self::ITEM_STUDENT_ID,
            DocumentType::ParentId => self::ITEM_PARENT_ID,
        };
    }

    /**
     * @return array<string, DocumentType> item_name => document type
     */
    public function uploadTypesByItemName(): array
    {
        $map = [];
        foreach (DocumentType::cases() as $type) {
            $map[$this->itemNameFor($type)] = $type;
        }

        return $map;
    }
}
