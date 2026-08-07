@props(['status'])
@php
    $map = [
        'draft' => 'bg-gray-100 text-gray-600',
        'pending' => 'bg-amber-50 text-amber-700',
        'submitted' => 'bg-blue-50 text-blue-700',
        'moderator_endorsed' => 'bg-blue-50 text-blue-700',
        'osa_reviewing' => 'bg-blue-50 text-blue-700',
        'docs_complete' => 'bg-green-50 text-green-700',
        'awaiting_physical' => 'bg-amber-50 text-amber-700',
        'approved' => 'bg-green-50 text-green-700',
        'verified' => 'bg-green-50 text-green-700',
        'denied' => 'bg-red-50 text-red-700',
        'revision_needed' => 'bg-red-50 text-red-700',
        'rejected' => 'bg-red-50 text-red-700',
        'accredited' => 'bg-green-50 text-green-700',
        'suspended' => 'bg-red-50 text-red-700',
        'inactive' => 'bg-gray-100 text-gray-600',
    ];
    $classes = $map[$status] ?? 'bg-gray-100 text-gray-600';
@endphp
<span {{ $attributes->merge(['class' => 'inline-block text-[11px] font-medium px-2.5 py-1 rounded-full '.$classes]) }}>
    {{ ucfirst(str_replace('_', ' ', $status)) }}
</span>
