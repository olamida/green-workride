@php
    $items = [
        ['group' => 'Go', 'icon' => 'route', 'label' => 'Find a ride', 'keywords' => 'trips board corridor search buses', 'url' => route('trips.index')],
        ['group' => 'Go', 'icon' => 'plus', 'label' => 'Publish a trip', 'keywords' => 'drive volunteer free ride', 'url' => route('trips.create')],
        ['group' => 'Go', 'icon' => 'ticket', 'label' => 'My rides', 'keywords' => 'bookings history receipts', 'url' => route('bookings.index')],
        ['group' => 'Money', 'icon' => 'wallet', 'label' => 'Wallet & top-up', 'keywords' => 'cash subsidy balance transfer withdraw', 'url' => route('wallet.index')],
        ['group' => 'Money', 'icon' => 'gift', 'label' => 'Rewards & Green Points', 'keywords' => 'redeem points campaigns', 'url' => route('rewards.index')],
        ['group' => 'Earn', 'icon' => 'target', 'label' => 'Missions', 'keywords' => 'volunteer activities challenges bonus sponsor', 'url' => route('missions.index')],
        ['group' => 'Earn', 'icon' => 'activity', 'label' => 'Impact', 'keywords' => 'co2 fuel saved trees certificate', 'url' => route('impact.index')],
        ['group' => 'Verify', 'icon' => 'shield', 'label' => 'Verify your identity', 'keywords' => 'nin workplace level driver docs', 'url' => route('verification.index')],
        ['group' => 'Shop', 'icon' => 'coins', 'label' => 'Commodities market', 'keywords' => 'gold rice maize fuel invest', 'url' => route('commodities.index')],
        ['group' => 'Shop', 'icon' => 'bag', 'label' => 'Shop', 'keywords' => 'orders products', 'url' => route('shop.index')],
        ['group' => 'Explore', 'icon' => 'map-pin', 'label' => 'Road map', 'keywords' => 'potholes heatmap iri fct', 'url' => route('road.map')],
        ['group' => 'Explore', 'icon' => 'grid', 'label' => 'Dashboard', 'keywords' => 'home overview', 'url' => route('dashboard')],
    ];

    if (auth()->user()->isAdmin()) {
        $items[] = ['group' => 'Control Tower', 'icon' => 'command', 'label' => 'Ops Control Tower', 'keywords' => 'admin ops dashboard business gtfs', 'url' => route('admin.dashboard')];
    }

    $items[] = ['group' => 'Session', 'icon' => 'log-out', 'label' => 'Sign out', 'keywords' => 'logout', 'action' => 'submit', 'form' => 'logout-form'];
@endphp

<div x-data="commandPalette()" @keydown.escape.window="open = false">
    <script type="application/json" id="wr-command-data">@json($items)</script>

    <div x-show="open" x-transition.opacity.duration.150ms
         class="fixed inset-0 z-[70] bg-ink-950/60 p-4 backdrop-blur-sm"
         @click.self="open = false">
            <div x-show="open" x-transition.scale.origin.top.duration.150ms
                 class="mx-auto mt-16 w-full max-w-xl overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-2xl">
                <div class="flex items-center gap-3 border-b border-ink-100 px-4">
                    <x-icon name="search" class="h-4 w-4 shrink-0 text-ink-400" />
                    <input x-ref="input" x-model="query"
                           type="text" placeholder="Search or jump to…  (↑ ↓ to move, ↵ to open)"
                           @keydown.arrow-down.prevent="move(1)"
                           @keydown.arrow-up.prevent="move(-1)"
                           @keydown.enter.prevent="go"
                           class="h-14 w-full bg-transparent text-sm text-ink-900 placeholder-ink-400 focus:outline-none"
                           role="combobox" aria-expanded="true">
                    <kbd class="rounded-md border border-ink-200 bg-paper px-1.5 py-0.5 font-mono text-[10px] text-ink-400">ESC</kbd>
                </div>

                <div class="wr-scroll max-h-80 overflow-y-auto p-2">
                    <template x-for="(item, i) in filtered" :key="item.label">
                        <div>
                            <p x-show="i === 0 || filtered[i - 1].group !== item.group"
                               class="px-3 pb-1 pt-3 text-[10px] font-semibold uppercase tracking-widest text-ink-400"
                               x-text="item.group"></p>
                            <a href="#" @click.prevent="index = i; go()" @mouseenter="index = i"
                               :class="index === i ? 'bg-forest-50 text-forest-800' : 'text-ink-700'"
                               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium">
                                <span :class="index === i ? 'bg-forest-600 text-white' : 'bg-paper text-ink-500'"
                                      class="flex h-8 w-8 items-center justify-center rounded-lg">
                                    <x-icon name="search" class="h-4 w-4" />
                                </span>
                                <span class="flex-1" x-text="item.label"></span>
                                <span :class="index === i ? 'opacity-100' : 'opacity-0'" class="transition">
                                    <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                                </span>
                            </a>
                        </div>
                    </template>
                    <p x-show="!filtered.length" class="px-3 py-8 text-center text-sm text-ink-400">
                        No matches for “<span x-text="query"></span>”
                    </p>
                </div>

                <div class="flex items-center justify-between border-t border-ink-100 bg-paper px-4 py-2.5 text-[11px] text-ink-400">
                    <p class="font-mono">WorkRide · ⌘K</p>
                    <p>Search the whole app, not the menu.</p>
                </div>
            </div>
        </div>
    </div>
</div>
