<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button type="button" @click="open = !open" aria-label="Profile menu" aria-expanded="false" :aria-expanded="open.toString()"
            class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2 transition hover:bg-ink-100">
        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-forest-600 text-sm font-bold text-white">
            {{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}
        </span>
        <span class="hidden text-left leading-tight lg:block">
            <span class="block max-w-[9rem] truncate text-xs font-semibold text-ink-900">{{ auth()->user()->name }}</span>
            <span class="block text-[10px] text-ink-500">L{{ auth()->user()->verification_level->value }} · {{ auth()->user()->verificationLevelLabel() }}</span>
        </span>
        <span :class="open ? 'rotate-180' : ''" class="transition-transform duration-200">
            <x-icon name="chevron-down" class="h-3.5 w-3.5 text-ink-400" />
        </span>
    </button>

    <div x-show="open" x-transition.scale.origin.top.right.duration.120ms @click.away="open = false"
         class="wr-scroll absolute right-0 top-full z-50 mt-2 max-h-[calc(100vh-6rem)] w-72 origin-top-right overflow-y-auto rounded-2xl border border-ink-200 bg-white p-2 shadow-2xl">
        <div class="rounded-xl bg-paper px-4 py-3">
            <p class="text-sm font-semibold text-ink-900">{{ auth()->user()->name }}</p>
            <p class="truncate text-xs text-ink-500">{{ auth()->user()->email }}</p>
        </div>

        <p class="px-3 pb-1 pt-3 text-[10px] font-semibold uppercase tracking-widest text-ink-400">Your workspace</p>
        <div class="grid grid-cols-2 gap-1">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-ink-700 transition hover:bg-forest-50 hover:text-forest-800">
                <x-icon name="grid" class="h-4 w-4 text-ink-400" /> Dashboard
            </a>
            <a href="{{ route('wallet.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-ink-700 transition hover:bg-forest-50 hover:text-forest-800">
                <x-icon name="wallet" class="h-4 w-4 text-ink-400" /> Wallet
            </a>
            <a href="{{ route('verification.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-ink-700 transition hover:bg-forest-50 hover:text-forest-800">
                <x-icon name="shield" class="h-4 w-4 text-ink-400" /> Verify
            </a>
            <a href="{{ route('employers.self') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-ink-700 transition hover:bg-forest-50 hover:text-forest-800">
                <x-icon name="building" class="h-4 w-4 text-ink-400" /> Employer
            </a>
            <a href="{{ route('commodities.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-ink-700 transition hover:bg-forest-50 hover:text-forest-800">
                <x-icon name="coins" class="h-4 w-4 text-ink-400" /> Commodities
            </a>
            <a href="{{ route('shop.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-ink-700 transition hover:bg-forest-50 hover:text-forest-800">
                <x-icon name="bag" class="h-4 w-4 text-ink-400" /> Shop
            </a>
            <a href="{{ route('road.map') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-ink-700 transition hover:bg-forest-50 hover:text-forest-800">
                <x-icon name="map-pin" class="h-4 w-4 text-ink-400" /> Road map
            </a>
            <a href="{{ route('demand.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-ink-700 transition hover:bg-forest-50 hover:text-forest-800">
                <x-icon name="signal" class="h-4 w-4 text-ink-400" /> Demand check-in
            </a>
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-ink-700 transition hover:bg-forest-50 hover:text-forest-800">
                <x-icon name="shield" class="h-4 w-4 text-ink-400" /> Profile & safety
            </a>
        </div>

        @if (auth()->user()->isAdmin())
            <p class="px-3 pb-1 pt-3 text-[10px] font-semibold uppercase tracking-widest text-ink-400">Ops</p>
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-ink-700 transition hover:bg-forest-50 hover:text-forest-800">
                <x-icon name="command" class="h-4 w-4 text-ink-400" /> Control Tower
            </a>
        @endif

        <div x-data="installApp" x-show="canInstall" x-cloak class="mt-2 border-t border-ink-100 pt-2">
            <button type="button" @click="install()" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-ink-700 transition hover:bg-forest-50 hover:text-forest-800">
                <x-icon name="download" class="h-4 w-4 text-ink-400" /> Install app
            </button>
        </div>

        <div class="mt-2 border-t border-ink-100 pt-2">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50">
                    <x-icon name="log-out" class="h-4 w-4" /> Sign out
                </button>
            </form>
        </div>
    </div>
</div>
