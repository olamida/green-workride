{{-- Global bottom tab bar — fixed, 5 items max, thumb-friendly --}}
<div x-data="bottomTabBar()"
     class="fixed inset-x-0 bottom-0 z-40 border-t border-ink-200/80 bg-white/90 pb-safe backdrop-blur-lg lg:hidden"
     role="navigation"
     aria-label="Main navigation">
    <div class="mx-auto grid max-w-lg grid-cols-5">
        {{-- Go / Home --}}
        <a href="{{ route('go') }}"
           class="flex flex-col items-center gap-1 py-2.5 text-[10px] font-semibold {{ request()->routeIs('go') ? 'text-forest-600' : 'text-ink-500' }}"
           aria-current="{{ request()->routeIs('go') ? 'page' : 'false' }}"
           aria-label="Go - Find rides">
            <span class="relative">
                <x-icon name="map-pin" class="h-5 w-5" />
                @if (request()->routeIs('go'))
                    <span class="absolute -bottom-1.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-forest-500" aria-hidden="true"></span>
                @endif
            </span>
            Go
        </a>

        {{-- My Rides --}}
        <a href="{{ route('bookings.index') }}"
           class="flex flex-col items-center gap-1 py-2.5 text-[10px] font-semibold {{ request()->routeIs('bookings.*') ? 'text-forest-600' : 'text-ink-500' }}"
           aria-current="{{ request()->routeIs('bookings.*') ? 'page' : 'false' }}"
           aria-label="My Rides">
            <span class="relative">
                <x-icon name="ticket" class="h-5 w-5" />
                @if (request()->routeIs('bookings.*'))
                    <span class="absolute -bottom-1.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-forest-500" aria-hidden="true"></span>
                @endif
            </span>
            Rides
        </a>

        {{-- Wallet --}}
        <a href="{{ route('wallet.index') }}"
           class="flex flex-col items-center gap-1 py-2.5 text-[10px] font-semibold {{ request()->routeIs('wallet.*') ? 'text-forest-600' : 'text-ink-500' }}"
           aria-current="{{ request()->routeIs('wallet.*') ? 'page' : 'false' }}"
           aria-label="Wallet">
            <span class="relative">
                <x-icon name="wallet" class="h-5 w-5" />
                @if (request()->routeIs('wallet.*'))
                    <span class="absolute -bottom-1.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-forest-500" aria-hidden="true"></span>
                @endif
            </span>
            Wallet
        </a>

        {{-- Impact --}}
        <a href="{{ route('impact.index') }}"
           class="flex flex-col items-center gap-1 py-2.5 text-[10px] font-semibold {{ request()->routeIs('impact.*') ? 'text-forest-600' : 'text-ink-500' }}"
           aria-current="{{ request()->routeIs('impact.*') ? 'page' : 'false' }}"
           aria-label="Impact & Green Points">
            <span class="relative">
                <x-icon name="leaf" class="h-5 w-5" />
                @if (request()->routeIs('impact.*'))
                    <span class="absolute -bottom-1.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-forest-500" aria-hidden="true"></span>
                @endif
            </span>
            Impact
        </a>

        {{-- Profile / More --}}
        <button type="button"
                @click="more = true"
                class="flex flex-col items-center gap-1 py-2.5 text-[10px] font-semibold {{ request()->routeIs('profile.*', 'verification.*', 'rewards.*', 'missions.*', 'commodities.*', 'shop.*', 'road.map') ? 'text-forest-600' : 'text-ink-500' }}"
                aria-label="More options"
                aria-expanded="false"
                aria-controls="mobile-more-sheet">
            <span class="relative">
                <x-icon name="user" class="h-5 w-5" />
                @if (request()->routeIs('profile.*', 'verification.*', 'rewards.*', 'missions.*', 'commodities.*', 'shop.*', 'road.map'))
                    <span class="absolute -bottom-1.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-forest-500" aria-hidden="true"></span>
                @endif
            </span>
            Profile
        </button>
    </div>

    {{-- "More" full-screen sheet — mobile escape hatch for every other feature --}}
    <div x-show="more"
         x-transition.opacity.duration.150ms
         class="fixed inset-0 z-[60] bg-ink-950/60 backdrop-blur-sm lg:hidden"
         @click.away="more = false"
         aria-hidden="true"></div>

    <div x-show="more"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         class="fixed inset-x-0 bottom-0 z-[61] rounded-t-3xl border-t border-ink-200 bg-white pb-safe lg:hidden"
         id="mobile-more-sheet"
         role="dialog"
         aria-modal="true"
         aria-labelledby="more-sheet-title">
        <div class="mx-auto h-1.5 w-10 rounded-full bg-ink-200 mt-3" aria-hidden="true"></div>
        <div class="wr-scroll max-h-[72vh] overflow-y-auto px-4 pb-6 pt-3">
            <div class="mb-3 flex items-center justify-between">
                <p class="font-heading text-base font-semibold text-ink-900" id="more-sheet-title">More</p>
                <button type="button"
                        @click="more = false"
                        class="rounded-full p-1.5 text-ink-400 hover:bg-ink-100"
                        aria-label="Close">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <a href="{{ route('rewards.index') }}"
                   class="flex flex-col items-center gap-1.5 rounded-2xl border border-ink-100 bg-paper px-2 py-3.5 text-center text-[11px] font-medium text-ink-700">
                    <x-icon name="gift" class="h-5 w-5 text-forest-600" /> Rewards
                </a>
                <a href="{{ route('missions.index') }}"
                   class="flex flex-col items-center gap-1.5 rounded-2xl border border-ink-100 bg-paper px-2 py-3.5 text-center text-[11px] font-medium text-ink-700">
                    <x-icon name="target" class="h-5 w-5 text-forest-600" /> Missions
                </a>
                <a href="{{ route('verification.index') }}"
                   class="flex flex-col items-center gap-1.5 rounded-2xl border border-ink-100 bg-paper px-2 py-3.5 text-center text-[11px] font-medium text-ink-700">
                    <x-icon name="shield" class="h-5 w-5 text-forest-600" /> Verify ID
                </a>
                <a href="{{ route('commodities.index') }}"
                   class="flex flex-col items-center gap-1.5 rounded-2xl border border-ink-100 bg-paper px-2 py-3.5 text-center text-[11px] font-medium text-ink-700">
                    <x-icon name="coins" class="h-5 w-5 text-forest-600" /> Commodities
                </a>
                <a href="{{ route('shop.index') }}"
                   class="flex flex-col items-center gap-1.5 rounded-2xl border border-ink-100 bg-paper px-2 py-3.5 text-center text-[11px] font-medium text-ink-700">
                    <x-icon name="bag" class="h-5 w-5 text-forest-600" /> Shop
                </a>
                <a href="{{ route('road.map') }}"
                   class="flex flex-col items-center gap-1.5 rounded-2xl border border-ink-100 bg-paper px-2 py-3.5 text-center text-[11px] font-medium text-ink-700">
                    <x-icon name="map" class="h-5 w-5 text-forest-600" /> Road Map
                </a>
                <a href="{{ route('dashboard') }}"
                   class="flex flex-col items-center gap-1.5 rounded-2xl border border-ink-100 bg-paper px-2 py-3.5 text-center text-[11px] font-medium text-ink-700">
                    <x-icon name="layout-dashboard" class="h-5 w-5 text-forest-600" /> Dashboard
                </a>
                <button type="button"
                        @click="more = false; install()"
                        x-show="canInstall"
                        x-cloak
                        class="flex flex-col items-center gap-1.5 rounded-2xl border border-ink-100 bg-paper px-2 py-3.5 text-center text-[11px] font-medium text-ink-700">
                    <x-icon name="download" class="h-5 w-5 text-forest-600" /> Install App
                </button>
                @if (auth()->check() && auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex flex-col items-center gap-1.5 rounded-2xl border border-ink-100 bg-paper px-2 py-3.5 text-center text-[11px] font-medium text-ink-700">
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

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('bottomTabBar', () => ({
        more: false,
        canInstall: false,
        init() {
            this.canInstall = Boolean(window.deferredInstallPrompt);
            window.addEventListener('wr-install-ready', () => { this.canInstall = true; });
            window.addEventListener('appinstalled', () => {
                this.canInstall = false;
                window.deferredInstallPrompt = null;
            });
        },
        install() {
            this.more = false;
            const prompt = window.deferredInstallPrompt;
            if (!prompt) return;
            prompt.prompt();
            prompt.userChoice.finally(() => {
                window.deferredInstallPrompt = null;
                this.canInstall = false;
            });
        }
    }));
});
</script>