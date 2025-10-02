<x-app-layout>
  <div class="max-w-5xl mx-auto mt-8">
    <h1 class="text-2xl font-bold mb-4">Events with a Waitlist</h1>

    @if ($events->isEmpty())
      <p class="text-gray-700">None of your events currently have a waitlist.</p>
    @else
      <table class="min-w-full border">
        <thead>
          <tr class="bg-gray-50">
            <th class="p-2 border text-left">Event</th>
            <th class="p-2 border text-left">Starts</th>
            <th class="p-2 border text-left">Bookings</th>
            <th class="p-2 border text-left">Waitlist</th>
            <th class="p-2 border text-left"></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($events as $ev)
            <tr>
              <td class="p-2 border">{{ $ev->title }}</td>
              <td class="p-2 border">{{ $ev->starts_at->format('d M Y, H:i') }}</td>
              <td class="p-2 border">
                {{ $ev->bookings_count }} / {{ $ev->capacity }}
                ({{ $ev->remaining_spots }} left)
              </td>
              <td class="p-2 border">{{ $ev->waitlist_count }}</td>
              <td class="p-2 border">
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>
</x-app-layout>
