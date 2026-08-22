{{-- FAB for "Add My Vehicle" on Go screen only --}}
@props([
    'enabled' => true,
])

@if ($enabled && auth()->check() && auth()->user()->canBookBenefits())
    <?php $fabUrl = route('trips.create'); ?>
    <button type="button"
            onclick="window.location.href='<?php echo $fabUrl; ?>'"
            class="fixed bottom-24 right-4 z-30 w-14 h-14 rounded-full bg-forest-600 text-white flex items-center justify-center shadow-xl shadow-forest-600/30 transition-all duration-200 hover:scale-105 hover:shadow-2xl active:scale-95 lg:hidden"
            aria-label="Add your vehicle — Be the driver"
            title="Add your vehicle">
        <x-icon name="plus" class="h-7 w-7" />
    </button>
@endif