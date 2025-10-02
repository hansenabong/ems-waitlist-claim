<x-app-layout>
    <div class="max-w-3xl mx-auto mt-8">
        @if (session('status'))
        <div class="mb-4 rounded-md bg-green-50 p-3 text-green-800">
            {{ session('status') }}
        </div>
        @endif
        @error('booking')
        <p class="mt-2 text-red-600">{{ $message }}</p>
        @enderror
        @if ($errors->has('delete'))
        <div class="mb-4 rounded-md bg-red-50 p-3 text-red-800">
            {{ $errors->first('delete') }}
        </div>
        @endif
        <h1 class="text-2xl font-bold">{{ $event->title }}</h1>
        <p>{{ $event->starts_at->format('d M Y, H:i') }}</p>
        <p>Location: {{ $event->location }}</p>
        <p>Capacity: {{ $event->capacity }}</p>
        <p>Organiser: {{ $event->organiser->name }}</p>
        <p>{{ $event->description }}</p>
        <p>Number of available spots: {{ $available }}</p>
        @if ($checkOrganiser)
        <div class="mt-6 flex items-center gap-3">
            <a href="{{ route('events.edit', $event) }}"
                class="inline-flex items-center px-4 py-2 rounded-md text-white bg-green-600 hover:bg-green-700
              focus:outline-none focus:ring-2 focus:ring-indigo-500">
                Edit
            </a>

            <form method="POST" action="{{ route('events.delete', $event) }}"
                onsubmit="return confirm('Delete this event?');" class="inline-block">
                @csrf
                @method('DELETE')
                <x-danger-button type="submit">Delete</x-danger-button>
            </form>
        </div>
        @elseif($checkUser)
        <form method="POST" action="{{ route('bookings.store', $event) }}">
            @csrf
            <x-primary-button type="submit">Book Now</x-primary-button>
        </form>
        @elseif($checkWaitlist)
        <form method="POST" action="{{ route('waitlist.store', $event) }}">
            @csrf
            <x-primary-button type="submit">Join Waitlist</x-primary-button>
        </form>
        @elseif($onWaitlist)
        <p class="mt-2 text-sm text-amber-700">You’re on the waiting list.</p>
        <form method="POST" action="{{ route('waitlist.delete', $event) }}" class="mt-2">
            @csrf @method('DELETE')
            <button class="px-4 py-2 rounded bg-gray-200 text-gray-800 hover:bg-gray-300">Leave Waitlist</button>
        </form>
        @endif
    </div>
</x-app-layout>