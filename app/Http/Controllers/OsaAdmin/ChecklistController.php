<?php

namespace App\Http\Controllers\OsaAdmin;

use App\Enums\ChecklistStatus;
use App\Http\Controllers\Controller;
use App\Models\ChecklistItem;
use App\Services\ChecklistService;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    public function update(Request $request, ChecklistItem $checklistItem, ChecklistService $checklist)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,submitted,verified'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['status'] === ChecklistStatus::Verified->value) {
            $checklist->verify($checklistItem, $validated['notes'] ?? null);
        } else {
            $checklistItem->update([
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? $checklistItem->notes,
            ]);
        }

        return back()->with('status', 'Checklist updated.');
    }
}
