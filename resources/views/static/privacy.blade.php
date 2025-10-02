<x-guest-layout>
  <h1 class="text-2xl font-semibold mb-4">Privacy Policy</h1>

  <h2 class="font-medium mt-2 mb-1">What data we collect</h2>
  <ul class="list-disc pl-6 space-y-1">
    <li>Your <strong>name</strong> and <strong>email</strong>.</li>
    <li>Your <strong>password</strong> (stored as a secure hash, never in plain text).</li>
    <li>Your <strong>role</strong> (organiser or attendee).</li>
    <li>Your <strong>booking history</strong> (events you create or attend).</li>
  </ul>

  <h2 class="font-medium mt-4 mb-1">Why we collect it</h2>
  <ul class="list-disc pl-6 space-y-1">
    <li><strong>Authentication</strong>: to create and secure your account and session.</li>
    <li><strong>Event participation</strong>: to let organisers manage events and attendees make bookings.</li>
  </ul>

  <h2 class="font-medium mt-4 mb-1">How it is stored & protected</h2>
  <ul class="list-disc pl-6 space-y-1">
    <li>Passwords are <strong>hashed</strong> using Laravel’s default password hashing.</li>
    <li>Application <strong>access control</strong> (auth/policies/middleware) limits who can view or modify data.</li>
    <li>Data is stored in the course demo database and is not shared with third parties. Teaching staff may access it for assessment only.</li>
  </ul>

  <h2 class="font-medium mt-4 mb-1">Your rights</h2>
  <ul class="list-disc pl-6 space-y-1">
    <li><strong>View</strong> your data by logging in (profile, events, and bookings).</li>
    <li><strong>Manage</strong> your data in-app (e.g., update profile, cancel bookings).</li>
    <li><strong>Delete</strong> your data by requesting account deletion from the teaching team for assessment purposes.</li>
  </ul>

  <p class="text-sm text-gray-500 mt-4">
    This is an academic artefact, not a production service. Do not reuse real passwords.
  </p>
</x-guest-layout>
