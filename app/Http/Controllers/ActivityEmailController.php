<?php

namespace App\Http\Controllers;

use App\Models\ActivityEmailDelivery;
use App\Services\ActivityEmailService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityEmailController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate(['status' => ['nullable', Rule::in(['pending', 'sent', 'failed'])]]);
        $status = $validated['status'] ?? null;

        return view('activity-email.index', [
            'deliveries' => ActivityEmailDelivery::query()
                ->when($status, fn ($query) => $query->where('status', $status))
                ->latest('id')->paginate(20)->withQueryString(),
            'status' => $status,
            'officeConfigured' => ActivityEmailService::officeAddress() !== null,
            'failedCount' => ActivityEmailDelivery::where('status', 'failed')->count(),
        ]);
    }
}
