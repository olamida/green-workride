{{-- Mobile bottom tab bar --}}
<div class="fixed bottom-0 left-0 right-0 z-40 md:hidden pb-safe" x-data="{ activeTab: 'go' }">
    <div class="flex items-center justify-around h-16 bg-white/95 backdrop-blur-sm border-t border-ink-200 shadow-lg">
        <a href="{{ route('go') }}"
           class="flex flex-col items-center gap-1 px-3 py-2 text-xs font-medium transition
               {{ request()->routeIs('go') ? 'text-forest-600' : 'text-ink-500' }}"
           @class="['text-forest-600']"
           aria-label="Go">
            <x-icon name="map-pin" class="h-5 w-5" />
            <span>Go</span>
        </a>

        <a href="{{ route('bookings.index') }}"
           class="flex flex-col items-center gap-1 px-3 py-2 text-xs font-medium transition
               {{ request()->routeIs('bookings.*') ? 'text-forest-600' : 'text-ink-500' }}"
           aria-label="My Rides">
            <x-icon name="calendar" class="h-5 w-5" />
            <span>Rides</span>
        </a>

        <a href="{{ route('wallet.index') }}"
           class="flex flex-col items-center gap-1 px-3 py-2 text-xs font-medium transition
               {{ request()->routeIs('wallet.*') ? 'text-forest-600' : 'text-ink-500' }}"
           aria-label="Wallet">
            <x-icon name="wallet" class="h-5 w-5" />
            <span>Wallet</span>
        </a>

        <a href="{{ route('impact.index') }}"
           class="flex flex-col items-center gap-1 px-3 py-2 text-xs font-medium transition
               {{ request()->routeIs('impact.*') ? 'text-forest-600' : 'text-ink-500' }}"
           aria-label="Impact">
            <x-icon name="leaf" class="h-5 w-5" />
            <span>Impact</span>
        </a>

        <a href="{{ route('profile.edit') }}"
           class="flex flex-col items-center gap-1 px-3 py-2 text-xs font-medium transition
               {{ request()->routeIs('profile.*') ? 'text-forest-600' : 'text-ink-500' }}"
           aria-label="Profile">
            <x-icon name="user" class="h-5 w-5" />
            <span>Profile</span>
        </a>
    </div>
</div>