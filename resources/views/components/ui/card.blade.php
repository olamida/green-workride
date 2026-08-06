@props(['class' => '', 'padding' => true])

<div {{ $attributes->merge(['class' => 'rounded-[var(--radius-card)] bg-white text-ink-900 shadow-[var(--shadow-card)] ring-1 ring-ink-900/5 '.$class]) }}>
    <div @class(['px-5 py-5', $padding => $padding])>
        {{ $slot }}
    </div>
</div>
