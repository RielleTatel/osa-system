@props(['label', 'physical' => false, 'status' => 'pending'])
{{-- Folder-tab + paperclip motif — the signature document card of the system. --}}
<div class="relative pt-3.5">
    <div class="absolute top-0 left-4 w-16 h-4 bg-slate-500 rounded-t-md"></div>
    <div class="relative bg-paper border border-slate-200 rounded-md p-4 shadow-sm">
        <div class="flex justify-between items-start gap-2">
            <div>
                <p class="text-sm font-medium text-ink-900">{{ $label }}</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ $physical ? 'Physical' : 'Digital' }}</p>
            </div>
            <x-heroicon-o-paper-clip class="w-4 h-4 text-slate-500 rotate-12 shrink-0" />
        </div>
        <x-status-pill :status="$status" class="mt-2" />
        @if(! empty(trim($slot)))
            <div class="mt-3">{{ $slot }}</div>
        @endif
    </div>
</div>
