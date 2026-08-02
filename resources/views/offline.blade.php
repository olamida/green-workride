@extends('layouts.public')

@section('title', 'Offline')

@section('content')
    <div class="mx-auto max-w-xl py-12 text-center">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-forest-50">
            <x-icon name="route" class="h-7 w-7 text-forest-600" />
        </span>
        <h1 class="mt-5 font-heading text-2xl font-bold text-ink-900">You're offline</h1>
        <p class="mx-auto mt-2 max-w-sm text-sm text-ink-500">
            Your connection dropped. The trip board you last saw is cached — once you're back online
            you can book fresh seats and chat live with your driver.
        </p>
        <a href="{{ route('trips.index') }}" class="mt-6 inline-block rounded-xl bg-forest-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
            Try again
        </a>
    </div>
@endsection
