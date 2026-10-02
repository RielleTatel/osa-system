<x-app-layout>
    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-hero-panel title="Email deliveries" />
        <p class="text-sm text-slate-600">{{ $failedCount }} failed deliveries. Sent means accepted by the mail server; it does not confirm arrival in the recipient's inbox.</p>
        @unless($officeConfigured)
            <div role="status" class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                Office mailbox is not configured with a valid address. Contact the system administrator to enable shared office notices. Individual OSA account notices continue to be scheduled.
            </div>
        @endunless
        <form method="GET" class="flex items-end gap-3">
            <div>
                <label for="delivery-status" class="block text-sm font-medium">Delivery status</label>
                <select id="delivery-status" name="status" class="mt-1 rounded-md border-slate-300">
                    <option value="">All deliveries</option>
                    @foreach(['pending' => 'Pending', 'sent' => 'Sent', 'failed' => 'Failed'] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <x-primary-button>Filter</x-primary-button>
        </form>
        <div class="rounded-xl border border-slate-200 bg-paper divide-y divide-slate-200">
            @forelse($deliveries as $delivery)
                <article class="p-4 space-y-2">
                    <div class="flex flex-wrap justify-between gap-2">
                        <a class="font-semibold text-navy-900 underline" href="{{ route('tracker.show', $delivery->activity_request_id) }}">#{{ $delivery->activity_request_id }} — {{ $delivery->summary['title'] }}</a>
                        <span class="font-semibold">{{ ucfirst($delivery->status) }}</span>
                    </div>
                    <p class="text-sm">{{ $delivery->summary['organization'] }} · {{ $delivery->event === 'director_ready' ? 'Ready for director review' : 'Initial submission' }}</p>
                    <p class="text-sm">To: {{ $delivery->recipient }}</p>
                    <p class="text-xs text-slate-600">{{ $delivery->attempts }} attempts · Queued {{ $delivery->created_at->format('Y-m-d H:i') }}</p>
                    @if($delivery->last_attempt_at)
                        <p class="text-xs text-slate-600">Last attempt: {{ $delivery->last_attempt_at->format('Y-m-d H:i') }}</p>
                    @endif
                    @if($delivery->sent_at)
                        <p class="text-xs text-slate-600">Accepted by mail server: {{ $delivery->sent_at->format('Y-m-d H:i') }}</p>
                    @elseif($delivery->status === 'failed')
                        <p class="text-sm text-red-700">Delivery failed. Ask the system administrator to check email delivery and retry this notice. Delivery reference: #{{ $delivery->id }}.</p>
                    @else
                        <p class="text-sm text-slate-600">Waiting for delivery or an automatic retry. Contact the system administrator if this remains pending.</p>
                    @endif
                </article>
            @empty
                <p class="p-6 text-slate-600">No email deliveries match this filter.</p>
            @endforelse
        </div>
        {{ $deliveries->links() }}
    </div>
</x-app-layout>
