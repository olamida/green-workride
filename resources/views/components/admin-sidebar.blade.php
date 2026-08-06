@props(['groups' => [], 'badges' => [], 'openGroup' => null])

@php
    $openGroup ??= array_key_first($groups);
@endphp

<div x-data="{ openGroup: @js($openGroup) }" class="flex flex-1 flex-col gap-3 overflow-y-auto">
    @foreach ($groups as $key => $group)
        @php
            $groupActive = collect($group['items'])->contains(fn ($item) => $item['active'] ?? false);
        @endphp
        <div>
            <button
                type="button"
                @click="openGroup = (openGroup === @js($key) ? null : @js($key))"
                :aria-expanded="openGroup === @js($key)"
                aria-controls="nav-group-{{ $key }}"
                @class([
                    'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider transition',
                    'text-ink-100' => $groupActive,
                    'text-ink-400 hover:bg-ink-800 hover:text-white' => ! $groupActive,
                ])
            >
                <x-icon :name="$group['icon']" class="h-4 w-4 shrink-0" />
                <span class="flex-1">{{ $group['label'] }}</span>
                <span :class="openGroup === @js($key) ? 'rotate-180' : ''" class="transition-transform duration-200">
                    <x-icon name="chevron-down" class="h-3.5 w-3.5" />
                </span>
            </button>

            <div id="nav-group-{{ $key }}" x-show="openGroup === @js($key)" x-cloak>
                <div class="mt-1 space-y-0.5">
                    @foreach ($group['items'] as $item)
                        @php
                            $active = $item['active'] ?? false;
                        @endphp
                        <a href="{{ $item['url'] }}" @class([
                            'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                            'bg-forest-600 text-white' => $active,
                            'text-ink-300 hover:bg-ink-800 hover:text-white' => ! $active,
                        ])>
                            <span class="flex-1">{{ $item['label'] }}</span>
                            @if (($item['badge'] ?? null) && ($badges[$item['badge']] ?? 0) > 0)
                                <span class="rounded-full bg-gold-400 px-1.5 py-0.5 text-[10px] font-bold leading-none text-ink-900">
                                    {{ $badges[$item['badge']] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
