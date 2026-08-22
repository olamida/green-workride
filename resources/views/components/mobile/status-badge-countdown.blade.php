{{-- StatusBadgeCountdown — soft-hold with live countdown --}}
@props([
    'status' => '',
    'label' => '',
    'countdown' => 0,
    'size' => 'md',
    'pulse' => false,
    'config' => [],
    'sizeClass' => '',
])

<div x-data="statusCountdown({{ $countdown }})"
     class="inline-flex items-center {{ $sizeClass }} rounded-full font-semibold {{ $config['bg'] }} {{ $config['text'] }} wr-transition-normal"
     role="timer" aria-live="polite" aria-atomic="true">
    <x-icon name="{{ $config['icon'] }}" class="h-3.5 w-3.5 flex-shrink-0" aria-hidden="true" />
    <span class="whitespace-nowrap">{{ $label }} — </span>
    <span class="font-mono tabular-nums" x-text="formatted" aria-label="Time remaining"></span>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('statusCountdown', (initialSeconds) => ({
        seconds: initialSeconds,
        interval: null,
        init() {
            this.interval = setInterval(() => {
                this.seconds = Math.max(0, this.seconds - 1);
                if (this.seconds <= 0) {
                    clearInterval(this.interval);
                    this.$dispatch('soft-hold-expired');
                }
            }, 1000);
        },
        get formatted() {
            const mins = Math.floor(this.seconds / 60);
            const secs = this.seconds % 60;
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        },
    }));
});
</script>