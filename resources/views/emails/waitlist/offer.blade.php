<h1>Good news!</h1>
<p>A seat just opened for <strong>{{ $event->title }}</strong></p>
<p>📍 {{ $event->location }}<br>
🗓️ {{ $event->starts_at->format('d M Y, H:i') }}</p>

<p><a href="{{ route('events.show', $event) }}">View Event</a></p>
<p><a href="{{ $claimUrl }}">Claim your seat</a> (expires in 15 minutes)</p>

<p>Thanks,<br>{{ config('app.name') }}</p>