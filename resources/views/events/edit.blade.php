<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Event
            </h2>
        </div>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('events.update', $event) }}">
                        {{csrf_field()}}
                        {{method_field('PUT')}}
                        {{-- Title (required, max 100) --}}
                        <div>
                            <x-input-label for="title" value="Title*" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                                maxlength="100" :value="old('title', $event->title)" required autofocus />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        {{-- Description (optional, textarea) --}}
                        <div>
                            <x-input-label for="description" value="Description (optional)" />
                            <textarea id="description" name="description" rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('description', $event->description) }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        {{-- Date & Time (required, future) --}}
                        @php $starts = old('starts_at') ?: optional($event->starts_at)->format('Y-m-d\TH:i'); @endphp
                        <div>
                            <x-input-label for="starts_at" value="Date & Time*" />
                            <input id="starts_at" name="starts_at" type="datetime-local"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                value="{{ $starts }}" required>
                            <x-input-error :messages="$errors->get('starts_at')" class="mt-2" />
                        </div>

                        {{-- Location (required, max 255) --}}
                        <div>
                            <x-input-label for="location" value="Location*" />
                            <x-text-input id="location" name="location" type="text" class="mt-1 block w-full"
                                maxlength="255" :value="old('location', $event->location)" required />
                            <x-input-error :messages="$errors->get('location')" class="mt-2" />
                        </div>

                        {{-- Capacity (required, integer 1..1000) --}}
                        <div>
                            <x-input-label for="capacity" value="Capacity*" />
                            <x-text-input id="capacity" name="capacity" type="number" class="mt-1 block w-40"
                                min="1" max="1000" step="1" :value="old('capacity', $event->capacity)" required />
                            <x-input-error :messages="$errors->get('capacity')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button class="ms-3">
                                Submit
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>