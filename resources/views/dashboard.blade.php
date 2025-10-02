<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
            <a href="{{ route('events.create') }}"
                class="inline-flex items-center px-4 py-2 rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                Create Event
            </a>
        </div>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
            <div class="mb-4 rounded-md bg-green-50 p-3 text-green-800">
                {{ session('status') }}
            </div>
            @endif
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @foreach ($events as $event)
                <div class="border rounded p-4 mb-4 bg-white shadow">
                    <a href="{{ route('events.show', $event->id) }}" class="text-xl font-semibold text-blue-600 hover:underline">
                        {{ $event->title }}
                    </a>
                    <p class="text-gray-700">
                        {{ \Carbon\Carbon::parse($event->starts_at)->format('d M Y, H:i') }} <br>
                        Capacity: {{ $event->capacity }} <br>
                        Current Booking: {{ $event->bookings_count }} <br>
                        Remaining Spot: {{ $event->remaining_spots }} <br>

                    </p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>