<x-app-layout>
  <div class="max-w-3xl mx-auto mt-8">
    @if (session('status'))
      <div class="mb-4 rounded-md bg-green-50 p-3 text-green-800">
        {{ session('status') }}
      </div>
    @endif

    <h1 class="text-2xl font-bold mb-4">Waitlists I’m on</h1>

    @if ($entries->isEmpty())
      <p class="text-gray-700">You’re not on any waitlists.</p>
    @else
      <ul class="space-y-3">
        @foreach ($entries as $wl)
          <li class="p-4 rounded border flex items-start justify-between">
            <div>
              <div class="font-semibold">{{ $wl->event->title }}</div>
              <div class="text-sm text-gray-600">
                {{ $wl->event->starts_at->format('d M Y, H:i') }} — {{ $wl->event->location }}
              </div>
            </div>
            <form method="POST"
                  action="{{ route('waitlist.delete', $wl->event) }}"
                  onsubmit="return confirm('Leave this waitlist?')">
              @csrf @method('DELETE')
              <button class="px-3 py-1.5 rounded bg-gray-200 hover:bg-gray-300">Leave</button>
            </form>
          </li>
        @endforeach
      </ul>
    @endif
  </div>
</x-app-layout>
