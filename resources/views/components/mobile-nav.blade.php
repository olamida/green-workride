<div x-data="{ more: false }" @keydown.escape.window="more = false">
    {{-- Bottom tab bar — the Uber/Bolt mobile pattern: 5 core destinations. --}}
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-ink-200/80 bg-white/90 pb-[env(safe-area-inset-bottom)] backdrop-blur-lg lg:hidden">
        <div class="mx-auto grid max-w-lg grid-cols-5">
            <a href="{{ route('trips.index') }}" class="flex flex-col items-center gap-1 py-2.5 text-[10px] font-semibold {{ request()->routeIs('trips.*') ? 'text-forest-600' : 'text-ink-500' }}">
                <span class="relative">
                    <x-icon name="route" class="h-5 w-5" />
                    @if (request()->routeIs('trips.*'))
                        <span class="absolute -bottom-1.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-forest-500"></span>
                    @endif
                </span>
                Go
            </a>
            <a href="{{ route('bookings.index') }}" class="flex flex-col items-center gap-1 py-2.5 text-[10px] font-semibold {{ request()->routeIs('bookings.*') ? 'text-forest-600' : 'text-ink-500' }}">
                <span class="relative">
                    <x-icon name="ticket" class="h-5 w-5" />
                    @if (request()->routeIs('bookings.*'))
                        <span class="absolute -bottom-1.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-forest-500"></span>
                    @endif
                </span>
                Rides
            </a>
            <a href="{{ route('wallet.index') }}" class="flex flex-col items-center gap-1 py-2.5 text-[10px] font-semibold {{ request()->routeIs('wallet.*') ? 'text-forest-600' : 'text-ink-500' }}">
                <span class="relative">
                    <x-icon name="wallet" class="h-5 w-5" />
                    @if (request()->routeIs('wallet.*'))
                        <span class="absolute -bottom-1.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-forest-500"></span>
                    @endif
                </span>
                Wallet
            </a>
            <a href="{{ route('rewards.index') }}" class="flex flex-col items-center gap-1 py-2.5 text-[10px] font-semibold {{ request()->routeIs('rewards.*') ? 'text-forest-600' : 'text-ink-500' }}">
                <span class="relative">
                    <x-icon name="gift" class="h-5 w-5" />
                    @if (request()->routeIs('rewards.*'))
                        <span class="absolute -bottom-1.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-forest-500"></span>
                    @endif
                </span>
                Rewards
            </a>
            <button type="button" @click="more = true" class="flex flex-col items-center gap-1 py-2.5 text-[10px] font-semibold {{ request()->routeIs('missions.*', 'impact.*', 'verification.*', 'commodities.*', 'shop.*') || request()->routeIs('road.map') ? 'text-forest-600' : 'text-ink-500' }}">
                <span class="relative">
                    <x-icon name="grid" class="h-5 w-5" />
                    @if (request()->routeIs('missions.*', 'impact.*', 'verification.*', 'commodities.*', 'shop.*') || request()->routeIs('road.map'))
                        <span class="absolute -bottom-1.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-forest-500"></span>
                    @endif
                </span>
                More
            </button>
        </div>
    </nav>

    {{-- "More" full-screen sheet — the mobile escape hatch for every other feature. --}}
    <div x-show="more" x-transition.opacity.duration.150ms class="fixed inset-0 z-[60] bg-ink-950/60 backdrop-blur-sm lg:hidden" @click.away="more = false"></div>
    <div x-show="more" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         class="fixed inset-x-0 bottom-0 z-[61] rounded-t-3xl border-t border-ink-200 bg-white pb-[env(safe-area-inset-bottom)] lg:hidden">
        <div class="mx-auto h-1 w-10 rounded-full bg-ink-200" style="margin-top: 8px"></div>
        <div class="wr-scroll max-h-[72vh] overflow-y-auto px-4 pb-6 pt-3">
            <div class="mb-3 flex items-center justify-between">
                <p class="font-heading text-base font-semibold text-ink-900">More</p>
                <button type="button" @click="more = false" class="rounded-full p-1.5 text-ink-400 hover:bg-ink-100" aria-label="Close">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <a href="{{ route('missions.index') }}" class="flex flex-col items-center gap-1.5 rounded-2xl border border-ink-100 bg-paper px-2 py-3.5 text-center text-[11px] font-medium text-ink-700">
                    <x-icon name="target" class="h-5 w-5 text-forest-600" /> Missions
                </a>
                <a href="{{ route('impact.index') }}" class="flex flex-col items-center gap-1.5 rounded-2xl border border-ink-100 bg-paper px-2 py-3.5 text-center text-[11px] font-medium text-ink-700">
                    <x-icon name="activity" class="h-5 w-5 text-forest-600" /> Impact
                </a>
                <a href="{{ route('verification.index') }}" class="flex flex-col items-center gap-1.5 rounded-2xl border border-ink-100 bg-paper px-2 py-3.5 text-center text-[11px] font-medium text-ink-700">
                    <x-icon name="shield" class="h-5 w-5 text-forest-600" /> Verify
                </a>
                <a href="{{ route('commodities.index') }}" class="flex flex-col items-center gap-1.5 rounded-2xl border border-ink-100 bg-paper px-2 py-3.5 text-center text-[11px] font-medium text-ink-700">
                    <x-icon name="coins" class="h-5 w-5 text-forest-600" /> Commodities
                </a>
                <a href="{{ route('shop.index') }}" class="flex flex-col items-center gap-1.5 rounded-2xl border border-ink-100 bg-paper px-2 py-3.5 text-center text-[11px] font-medium text-ink-700">
                    <x-icon name="bag" class="h-5 w-5 text-forest-600" /> Shop
                </a>
                <a href="{{ route('road.map') }}" class="flex flex-col items-center gap-1.5 rounded-2xl border border-ink-100 bg-paper px-2 py-3.5 text-center text-[11px] font-medium text-ink-700">
                    <x-icon name="map-pin" class="h-5 w-5 text-forest-600" /> Road map
                </a>
                <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-1.5 rounded-2xl border border-ink-100 bg-paper px-2 py-3.5 text-center text-[11px] font-medium text-ink-700">
                    <x-icon name="grid" class="h-5 w-5 text-forest-600" /> Dashboard
                </a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="flex flex-col items-center gap-1.5 rounded-2xl border border-ink-100 bg-paper px-2 py-3.5 text-center text-[11px] font-medium text-ink-700">
                        <x-icon name="command" class="h-5 w-5 text-forest-600" /> Control Tower
                    </a>
                @endif
            </div>

            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <button class="flex w-full items-center justify-center gap-2 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-600">
                    <x-icon name="log-out" class="h-4 w-4" /> Sign out
                </button>
            </form>
        </div>
    </div>
</div>
