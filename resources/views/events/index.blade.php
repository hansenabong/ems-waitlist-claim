<x-app-layout>
    <div class="max-w-5xl mx-auto mt-8">
        <h1 class="text-2xl font-bold mb-4">Upcoming Events</h1>

        @foreach ($events as $event)
            <div class="border rounded p-4 mb-4 bg-white shadow">
                <a href="{{ route('events.show', $event) }}" class="text-xl font-semibold text-blue-600 hover:underline">
                    {{ $event->title }}
                </a>
                <p class="text-gray-700">
                    {{ $event->starts_at->format('d M Y, H:i') }} <br>
                    Location: {{ $event->location }}
                </p>
            </div>
        @endforeach

        <div class="mt-6">
            {{ $events->links() }} 
        </div>
    </div>
</x-app-layout>