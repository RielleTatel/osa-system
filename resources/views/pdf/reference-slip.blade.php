<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #16233F; font-size: 12px; }
        .hero { background: #0B1930; color: #fff; padding: 24px 28px; }
        .eyebrow { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: #C9D6EA; }
        .ref { color: #D4AF37; font-style: italic; font-size: 13px; margin-top: 8px; }
        .title { font-size: 20px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px; }
        .body { padding: 24px 28px; }
        .row { padding: 6px 0; border-bottom: 1px solid #E2E8F0; }
        .label { color: #5C77A6; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .value { font-size: 13px; margin-top: 2px; }
        h2 { font-size: 13px; margin: 20px 0 8px; color: #0B1930; }
        .item { padding: 4px 0; border-bottom: 1px solid #EEF1F6; }
        .amber { color: #7A5C14; }
        .note { margin-top: 24px; font-size: 11px; color: #5C77A6; border-top: 2px solid #D4AF37; padding-top: 10px; }
        .gold-bar { height: 4px; background: #D4AF37; }
    </style>
</head>
<body>
    <div class="hero">
        <div class="eyebrow">Ateneo de Zamboanga University &middot; Office of Student Affairs</div>
        <div class="ref">Ref. {{ $request->referenceSlip->reference_code }}</div>
        <div class="title">{{ $request->title }}</div>
    </div>
    <div class="gold-bar"></div>

    <div class="body">
        <div class="row"><div class="label">Organization</div><div class="value">{{ $request->organization->name }}</div></div>
        <div class="row"><div class="label">Activity type</div><div class="value">{{ $request->isOffCampus() ? 'Off-campus' : 'In-campus' }}</div></div>
        <div class="row"><div class="label">Date &amp; venue</div><div class="value">{{ $request->date_start->format('M j, Y') }} &middot; {{ $request->venue }}</div></div>
        <div class="row"><div class="label">Generated</div><div class="value">{{ $request->referenceSlip->generated_at->format('M j, Y g:i A') }}</div></div>

        <h2>Physical documents to bring to the OSA counter</h2>
        @foreach($request->checklistItems->where('is_physical', true) as $item)
            <div class="item amber">&#9744; {{ $item->item_name }}</div>
        @endforeach

        <div class="note">
            Present this slip at the Office of Student Affairs to claim the physical Pink/Blue form and begin the
            wet-signature chain. This slip certifies that the digital packet has been reviewed and completed in the system.
        </div>
    </div>
</body>
</html>
