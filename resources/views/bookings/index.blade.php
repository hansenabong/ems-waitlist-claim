<x-app-layout>
    <div class="max-w-5xl mx-auto mt-8">
        @if (session('status'))
        <div class="mb-4 rounded-md bg-green-50 p-3 text-green-800">
            {{ session('status') }}
        </div>
        @endif
        @if($bookings->isEmpty())
        <p>You have no bookings yet.</p>
        @else
        <ul class="space-y-2">
            @foreach ($bookings as $b)
            <li class="p-4 rounded border">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="font-semibold">{{ $b->event->title }}</div>
                        <div class="text-sm text-gray-600">
                            {{ optional($b->event->starts_at)->format('d M Y, H:i') }}
                        </div>
                        <div class="text-sm text-gray-600">{{ $b->event->location }}</div>
                    </div>

                    @if($b->event && $b->event->starts_at->isFuture())
                    <form method="POST" action="{{ route('bookings.delete', $b) }}"
                        onsubmit="return confirm('Cancel this booking?')" class="shrink-0">
                        @csrf @method('DELETE')
                        <button class="px-4 py-2 rounded bg-gray-200 text-gray-800 hover:bg-gray-300">
                            Cancel
                        </button>
                    </form>
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
        @endif

    </div>
</x-app-layout>