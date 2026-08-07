<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #16233F; font-size: 11px; }
        .header { text-align: center; padding: 16px; border-bottom: 2px solid #0B1930; }
        .header .sub { font-size: 10px; color: #5C77A6; }
        .header .title { font-size: 15px; font-weight: bold; margin-top: 6px; text-transform: uppercase; }
        .body { padding: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #C9D6EA; padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background: #F4F6FA; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #5C77A6; }
        h2 { font-size: 12px; margin: 16px 0 4px; }
        .yes { color: #15803d; font-weight: bold; }
        .no { color: #b91c1c; font-weight: bold; }
        .signoff { margin-top: 30px; }
        .sig-line { border-top: 1px solid #16233F; width: 260px; margin-top: 28px; padding-top: 4px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="sub">Republic of the Philippines &middot; Commission on Higher Education (CHED)</div>
        <div class="sub">Ateneo de Zamboanga University &middot; Zamboanga City, Region IX</div>
        <div class="title">OSA Form 3 — Local Off-Campus Activities Report of Compliance</div>
    </div>

    <div class="body">
        <h2>Basic information</h2>
        <table>
            <tr><th>Program</th><td>{{ $form->program_name }}</td><th>Course</th><td>{{ $form->course }}</td></tr>
            <tr><th>Destination &amp; venue</th><td>{{ $form->destination_venue }}</td><th>Inclusive dates</th><td>{{ $form->inclusive_dates }}</td></tr>
            <tr><th>No. of students</th><td>{{ $form->number_of_students }}</td><th>Personnel-in-charge</th><td>{{ $form->personnel_in_charge }}</td></tr>
        </table>

        <h2>Report before the activity</h2>
        <table>
            <thead><tr><th style="width:24px">#</th><th>Activity</th><th style="width:80px">Compliance</th><th style="width:30%">Remarks</th></tr></thead>
            <tbody>
                @foreach($form->complianceItems as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item->activity_label }}</td>
                        <td class="{{ $item->compliance ? 'yes' : 'no' }}">{{ $item->compliance ? 'Yes' : 'No' }}</td>
                        <td>{{ $item->remarks }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="signoff">
            <div class="sig-line">
                {{ $form->moderator_approval_status === 'approved' ? $form->moderator?->name : '' }}<br>
                Recommending Approval (Moderator)
                @if($form->moderator_approved_at) &middot; {{ $form->moderator_approved_at->format('M j, Y') }} @endif
            </div>
        </div>
    </div>
</body>
</html>
