<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>OSA activity notification</title></head>
<body>
    @if($delivery->event === 'director_ready')
        <h1>Ready for director review</h1>
        <p>The digital document checklist is complete. Director action required: review the activity and record your notation in the notation queue.</p>
    @else
        <h1>Activity submitted</h1>
        <p>A new activity has been submitted. It is awaiting moderator endorsement; OSA review is not yet required.</p>
    @endif
    <dl>
        <dt>Organization</dt><dd>{{ $delivery->summary['organization'] }}</dd>
        <dt>Activity</dt><dd>{{ $delivery->summary['title'] }}</dd>
        <dt>Proposed dates</dt><dd>{{ $delivery->summary['date_start'] }} to {{ $delivery->summary['date_end'] }}</dd>
        <dt>Request reference</dt><dd>#{{ $delivery->activity_request_id }}</dd>
    </dl>
    <p><a href="{{ route('tracker.show', $delivery->activity_request_id) }}">View activity (OSA login required)</a></p>
</body>
</html>
