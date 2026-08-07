@props(['title', 'reference' => null, 'eyebrow' => 'Office of Student Affairs'])
<div {{ $attributes->merge(['class' => 'bg-navy-gradient rounded-xl px-6 py-5 text-white']) }}>
    <div class="flex items-center gap-2 mb-3">
        <span class="w-7 h-7 rounded-full border-2 border-gold-500 flex items-center justify-center">
            <x-heroicon-o-pencil class="w-3.5 h-3.5 text-gold-500" />
        </span>
        <span class="text-sm font-medium">{{ $eyebrow }}</span>
    </div>
    @if($reference)
        <p class="text-xs italic font-wordmark text-gold-500 tracking-wide mb-1">Ref. {{ $reference }}</p>
    @endif
    <h1 class="font-display text-xl font-extrabold uppercase tracking-wide">{{ $title }}</h1>
    @if(! empty(trim($slot)))
        <div class="mt-2 text-slate-200 text-sm">{{ $slot }}</div>
    @endif
</div>
