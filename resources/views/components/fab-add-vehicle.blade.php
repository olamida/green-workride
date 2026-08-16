{{-- FAB: Add My Vehicle --}}
@if (auth()->check() && auth()->user()->canBookBenefits())
    <a href="{{ route('trips.create') }}"
       class="fixed bottom-24 right-4 z-30 md:hidden flex h-12 w-12 items-center justify-center rounded-full bg-forest-600 text-white shadow-lg transition hover:bg-forest-700 hover:scale-105 active:scale-95 focus:outline-none focus:ring-2 focus:ring-forest-500/30"
       aria-label="Add my vehicle — Publish a ride">
        <x-icon name="plus" class="h-6 w-6" />
    </a>
@endif