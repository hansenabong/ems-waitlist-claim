Good news! A seat just opened for: {{ $event->title }}
When: {{ $event->starts_at->format('d M Y, H:i') }}
Where: {{ $event->location }}

Claim your seat (15 minutes):
{!! $claimUrl !!} {{-- this is to ignore the prefix amp; from the url generated --}}
