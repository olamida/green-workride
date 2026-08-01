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
        <div class="flex min-h-screen">
            <aside class="fixed inset-y-0 left-0 z-40 hidden w-60 flex-col border-r border-ink-800 bg-ink-900 text-white md:flex">
                <div class="flex h-16 items-center gap-2 border-b border-ink-800 px-5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-forest-600 font-heading text-lg font-bold text-white">W</span>
                    <div>
                        <p class="font-heading text-sm font-semibold leading-tight">WorkRide</p>
                        <p class="text-[10px] uppercase tracking-widest text-ink-400">Control Tower</p>
                    </div>
                </div>

                <nav class="flex flex-1 flex-col gap-1 p-3">
                    <x-admin-nav-link :active="request()->routeIs('admin.dashboard')" href="{{ route('admin.dashboard') }}">Overview</x-admin-nav-link>
                    <x-admin-nav-link :active="request()->routeIs('admin.verifications.*')" href="{{ route('admin.verifications.index') }}">Verifications</x-admin-nav-link>
                    <x-admin-nav-link :active="request()->routeIs('admin.users.*')" href="{{ route('admin.users.index') }}">Users</x-admin-nav-link>
                    <x-admin-nav-link :active="request()->routeIs('admin.workplaces.*')" href="{{ route('admin.workplaces.index') }}">Workplaces</x-admin-nav-link>
                    <x-admin-nav-link :active="request()->routeIs('admin.subsidies.*')" href="{{ route('admin.subsidies.index') }}">Subsidies</x-admin-nav-link>
                    <x-admin-nav-link :active="request()->routeIs('admin.gtfs.*')" href="{{ route('admin.gtfs.index') }}">GTFS Publisher</x-admin-nav-link>
                    <x-admin-nav-link :active="request()->routeIs('admin.road.*')" href="{{ route('admin.road.index') }}">Road Intelligence</x-admin-nav-link>
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

            <div class="flex-1 md:ml-60">
                <header class="flex h-16 items-center justify-between border-b border-ink-200 bg-white px-4 md:px-8">
                    <h1 class="font-heading text-base font-semibold text-ink-900">@yield('page', 'Ops Control Tower')</h1>
                    <a href="{{ route('dashboard') }}" class="text-xs font-medium text-forest-600 hover:underline">← Rider app</a>
                </header>

                <main class="p-4 md:p-8">
                    @if (session('status'))
                        <x-flash>{{ session('status') }}</x-flash>
                    @endif
                    @yield('content')
                </main>
            </div>
        </div>
    </body>
</html>
