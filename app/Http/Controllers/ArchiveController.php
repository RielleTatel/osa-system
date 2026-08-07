<?php

namespace App\Http\Controllers;

use App\Enums\ActivityStatus;
use App\Models\ActivityRequest;
use App\Models\Organization;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    public function index(Request $request)
    {
        $requests = ActivityRequest::query()
            ->whereIn('status', [ActivityStatus::Approved, ActivityStatus::Denied])
            ->when($request->q, fn ($query, $term) => $query->where(
                fn ($w) => $w->where('title', 'like', "%{$term}%")->orWhere('venue', 'like', "%{$term}%"),
            ))
            ->when($request->organization_id, fn ($query, $org) => $query->where('organization_id', $org))
            ->when($request->year, fn ($query, $year) => $query->whereYear('date_start', $year))
            ->with(['organization', 'approvals', 'documentUploads'])
            ->latest('date_start')
            ->paginate(20)
            ->withQueryString();

        return view('archive.index', [
            'requests' => $requests,
            'organizations' => Organization::orderBy('name')->get(),
        ]);
    }
}
