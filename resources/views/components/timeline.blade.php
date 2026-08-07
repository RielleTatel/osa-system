@props(['current'])
@php
    $stages = [
        'submitted' => 'Submitted',
        'moderator_endorsed' => 'Moderator endorsed',
        'osa_reviewing' => 'OSA reviewing',
        'docs_complete' => 'Digital docs complete',
        'awaiting_physical' => 'Awaiting physical forms',
        'approved' => 'Approved',
    ];
    $keys = array_keys($stages);
    $idx = array_search($current, $keys, true); // false for draft/denied/revision_needed
@endphp
<ol class="flex flex-wrap gap-y-2 items-center text-xs">
    @foreach($stages as $key => $label)
        @php $done = $idx !== false && array_search($key, $keys, true) <= $idx; @endphp
        <li @class([
            'flex items-center gap-1.5',
            "after:content-[''] after:w-6 after:h-px after:bg-slate-200 after:mx-2" => ! $loop->last,
        ])>
            <span @class([
                'w-2 h-2 rounded-full',
                'bg-gold-500' => $done,
                'bg-slate-200' => ! $done,
            ])></span>
            <span @class([
                'text-ink-900 font-medium' => $done,
                'text-gray-400' => ! $done,
            ])>{{ $label }}</span>
        </li>
    @endforeach
    @if(in_array($current, ['denied', 'revision_needed'], true))
        <li class="ml-3"><x-status-pill :status="$current" /></li>
    @endif
</ol>
