<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Ops Control Tower') — {{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-paper">
        @php
            $badges = [
                'verifications' => \App\Models\Verification::where('status', 'pending')->count(),
                'employers' => \App\Models\EmployerMember::where('status', 'pending')->count(),
            ];

            $navGroups = collect(config('admin_nav.groups'))->map(function (array $group, string $key) {
                $items = array_map(function (array $item) {
                    $patterns = array_map('trim', explode(',', $item['active'] ?? ''));

                    return [
                        'label' => $item['label'],
                        'url' => route($item['route']),
                        'active' => request()->routeIs(...$patterns),
                        'badge' => $item['badge'] ?? null,
                    ];
                }, $group['items'] ?? []);

                return [
                    'label' => $group['label'],
                    'icon' => $group['icon'],
                    'items' => $items,
                ];
            })->all();

            $activeGroupKey = null;
            foreach ($navGroups as $key => $group) {
                if (collect($group['items'])->contains(fn ($item) => $item['active'])) {
                    $activeGroupKey = $key;
                    break;
                }
            }
            $activeGroupKey ??= array_key_first($navGroups);

            $viewingAs = $viewingAs ?? false;
            $effectiveRole = $effectiveRole ?? auth()->user()->role;
        @endphp

        <div x-data="{ navOpen: false }" class="flex min-h-screen">
            {{-- Desktop sidebar --}}
            <aside class="fixed inset-y-0 left-0 z-40 hidden w-60 flex-col border-r border-ink-800 bg-ink-900 text-white md:flex">
                <div class="flex h-14 items-center gap-2 border-b border-ink-800 px-5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-forest-600 font-heading text-lg font-bold text-white">W</span>
                    <div>
                        <p class="font-heading text-sm font-semibold leading-tight">WorkRide</p>
                        <p class="text-[10px] uppercase tracking-widest text-ink-400">Control Tower</p>
                    </div>
                </div>

                <nav class="flex flex-1 flex-col gap-3 overflow-y-auto p-3">
                    <x-admin-nav-link :active="request()->routeIs('admin.dashboard')" href="{{ route('admin.dashboard') }}">
                        <x-icon name="target" class="h-4 w-4" />
                        <span>Overview</span>
                    </x-admin-nav-link>

                    <x-admin-sidebar :groups="$navGroups" :badges="$badges" :open-group="$activeGroupKey" />
                </nav>

                <div class="border-t border-ink-800 p-4 text-sm">
                    <p class="font-medium">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-ink-400">{{ auth()->user()->email }}</p>
                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button class="w-full rounded-lg border border-ink-700 px-3 py-1.5 text-xs font-medium text-ink-200 hover:bg-ink-800">
                            Sign out
                        </button>
                    </form>
                </div>
            </aside>

            {{-- Mobile drawer --}}
            <div x-cloak x-show="navOpen" class="fixed inset-0 z-50 md:hidden" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="absolute inset-0 bg-ink-900/60" @click="navOpen = false" aria-hidden="true"></div>
                <div class="absolute inset-y-0 left-0 flex w-72 flex-col bg-ink-900 text-white shadow-2xl" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0">
                    <div class="flex h-14 items-center justify-between border-b border-ink-800 px-5">
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-forest-600 font-heading text-lg font-bold">W</span>
                            <p class="font-heading text-sm font-semibold">WorkRide</p>
                        </div>
                        <button type="button" @click="navOpen = false" aria-label="Close menu" class="rounded-lg p-2 text-ink-300 hover:bg-ink-800 hover:text-white">
                            <x-icon name="log-out" class="h-5 w-5" />
                        </button>
                    </div>
                    <nav class="flex flex-1 flex-col gap-3 overflow-y-auto p-3">
                        <x-admin-nav-link :active="request()->routeIs('admin.dashboard')" href="{{ route('admin.dashboard') }}">
                            <x-icon name="target" class="h-4 w-4" />
                            <span>Overview</span>
                        </x-admin-nav-link>
                        <x-admin-sidebar :groups="$navGroups" :badges="$badges" :open-group="$activeGroupKey" />
                    </nav>
                    <div class="border-t border-ink-800 p-4 text-sm">
                        <p class="font-medium">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-ink-400">{{ auth()->user()->email }}</p>
                        <form method="POST" action="{{ route('logout') }}" class="mt-3">
                            @csrf
                            <button class="w-full rounded-lg border border-ink-700 px-3 py-1.5 text-xs font-medium text-ink-200 hover:bg-ink-800">Sign out</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="flex-1 pb-16 md:ml-60 md:pb-0">
                <header class="flex h-14 items-center justify-between gap-3 border-b border-ink-200 bg-white px-4 md:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button" @click="navOpen = true" aria-label="Open menu" class="rounded-lg p-2 text-ink-600 hover:bg-ink-100 md:hidden">
                            <x-icon name="menu" class="h-5 w-5" />
                        </button>
                        <h1 class="truncate font-heading text-base font-semibold text-ink-900">@yield('page', 'Ops Control Tower')</h1>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('dashboard') }}" class="hidden text-xs font-medium text-forest-600 hover:underline sm:block">← Rider app</a>

                        <div x-data="{ open: false }" class="relative">
                            <button
                                type="button"
                                @click="open = ! open"
                                @click.outside="open = false"
                                aria-haspopup="menu"
                                :aria-expanded="open"
                                class="flex items-center gap-1.5 rounded-lg border border-ink-200 px-2.5 py-1.5 text-xs font-medium text-ink-700 hover:bg-ink-50"
                            >
                                <span>View as: {{ $effectiveRole->label() }}</span>
                                <span :class="open ? 'rotate-180' : ''" class="transition-transform duration-200">
                                    <x-icon name="chevron-down" class="h-3.5 w-3.5" />
                                </span>
                            </button>

                            <div x-cloak x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" role="menu" class="absolute right-0 z-50 mt-2 w-48 overflow-hidden rounded-xl border border-ink-200 bg-white py-1 shadow-lg">
                                @foreach (\App\Services\RoleSwitcherService::VIEWABLE_ROLES as $viewable)
                                    <form method="POST" action="{{ route('admin.view-as') }}">
                                        @csrf
                                        <input type="hidden" name="role" value="{{ $viewable }}">
                                        <button type="submit" role="menuitem" class="flex w-full items-center justify-between px-3 py-2 text-left text-sm text-ink-700 hover:bg-ink-50">
                                            {{ \App\Enums\UserRole::from($viewable)->label() }}
                                            @if ($viewingAs && $effectiveRole->value === $viewable)
                                                <span class="text-forest-600">✓</span>
                                            @endif
                                        </button>
                                    </form>
                                @endforeach
                                <div class="my-1 border-t border-ink-100"></div>
                                <form method="POST" action="{{ route('admin.view-as.reset') }}">
                                    @csrf
                                    <button type="submit" role="menuitem" class="w-full px-3 py-2 text-left text-sm font-medium text-forest-600 hover:bg-ink-50">
                                        Back to admin view
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                @if ($viewingAs)
                    <div class="flex items-center justify-between gap-3 border-b border-gold-200 bg-gold-50 px-4 py-2 md:px-8">
                        <p class="text-xs font-medium text-gold-900">
                            Viewing as <strong>{{ $effectiveRole->label() }}</strong> — displays the Control Tower through this role's eyes. Admin controls are unchanged.
                        </p>
                        <form method="POST" action="{{ route('admin.view-as.reset') }}">
                            @csrf
                            <button class="shrink-0 text-xs font-semibold text-forest-600 hover:underline">Back to admin</button>
                        </form>
                    </div>
                @endif

                <main class="p-4 md:p-8">
                    @if (session('status'))
                        <x-flash>{{ session('status') }}</x-flash>
                    @endif
                    @yield('content')
                </main>
            </div>
        </div>

        {{-- Mobile bottom nav --}}
        <nav class="fixed inset-x-0 bottom-0 z-40 flex items-center justify-around border-t border-ink-200 bg-white/95 px-2 py-1.5 backdrop-blur md:hidden" aria-label="Mobile admin navigation">
            @php
                $mobileItems = [
                    ['route' => 'admin.dashboard', 'icon' => 'target', 'label' => 'Overview'],
                    ['route' => 'admin.ops.demand', 'icon' => 'signal', 'label' => 'Demand'],
                    ['route' => 'admin.fleet.index', 'icon' => 'truck', 'label' => 'Fleet'],
                    ['route' => 'admin.business.index', 'icon' => 'wallet', 'label' => 'Business'],
                ];
            @endphp
            @foreach ($mobileItems as $item)
                <a href="{{ route($item['route']) }}" @class([
                    'flex flex-col items-center gap-0.5 rounded-lg px-3 py-1.5 text-[10px] font-medium',
                    'text-forest-600' => request()->routeIs($item['route']),
                    'text-ink-500' => ! request()->routeIs($item['route']),
                ])>
                    <x-icon :name="$item['icon']" class="h-5 w-5" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </body>
</html>
