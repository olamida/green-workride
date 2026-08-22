{{-- BottomSheet — standard for filters, confirm, soft-hold — draggable spring 200ms --}}
@props([
    'title' => '',
    'open' => false,
    'height' => 'auto',        // 'half' | 'full' | 'auto' | specific height
    'showHandle' => true,
    'closeOnOverlayClick' => true,
    'id' => 'bottom-sheet',
])

<?php
$sheetId = $id;
$handleId = "{$sheetId}-handle";
$panelId = "{$sheetId}-panel";
$overlayId = "{$sheetId}-overlay";
?>

<div x-data="bottomSheet('{{ $sheetId }}', {{ json_encode($open) }})"
     x-init="init()"
     class="fixed inset-0 z-50 lg:hidden" aria-hidden="true">

    {{-- Overlay --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="$dispatch('close-bottom-sheet', { id: '{{ $sheetId }}' })"
         x-show:if="$attrs.closeOnOverlayClick"
         class="fixed inset-0 bg-ink-950/60 backdrop-blur-sm"
         id="{{ $overlayId }}"
         aria-hidden="true"></div>

    {{-- Sheet panel --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="transform translate-y-full"
         x-transition:enter-end="transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="transform translate-y-0"
         x-transition:leave-end="transform translate-y-full"
         @keydown.escape.window="$dispatch('close-bottom-sheet', { id: '{{ $sheetId }}' })"
         class="fixed inset-x-0 bottom-0 z-51 rounded-t-3xl border-t border-ink-200 bg-white pb-[env(safe-area-inset-bottom)]"
         id="{{ $panelId }}"
         role="dialog"
         aria-modal="true"
         aria-labelledby="{{ $title ? $handleId . '-title' : '' }}"
         :style="heightStyle">

        {{-- Drag handle --}}
        @if ($showHandle)
            <div class="mx-auto h-1.5 w-10 rounded-full bg-ink-200 mt-3 mb-2" id="{{ $handleId }}" aria-hidden="true"></div>
        @endif

        {{-- Title --}}
        @if ($title)
            <div class="px-4 pb-3 flex items-center justify-between">
                <h2 class="font-heading text-lg font-semibold text-ink-900" id="{{ $handleId }}-title">{{ $title }}</h2>
                <button type="button"
                        @click="$dispatch('close-bottom-sheet', { id: '{{ $sheetId }}' })"
                        class="rounded-full p-2 text-ink-400 hover:bg-ink-100 hover:text-ink-700"
                        aria-label="Close">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>
        @endif

        {{-- Content --}}
        <div class="wr-scroll max-h-[70vh] overflow-y-auto px-4 pb-6">
            {{ $slot }}
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('bottomSheet', (sheetId, initialOpen = false) => ({
        open: initialOpen,
        heightStyle: '',
        init() {
            this.$watch('open', (value) => {
                document.body.style.overflow = value ? 'hidden' : '';
                document.dispatchEvent(new CustomEvent('bottom-sheet-toggled', {
                    detail: { id: sheetId, open: value }
                }));
            });
            window.addEventListener('close-bottom-sheet', (e) => {
                if (e.detail.id === sheetId) this.close();
            });
            this.updateHeight();
        },
        openSheet() { this.open = true; },
        close() { this.open = false; },
        toggle() { this.open = !this.open; },
        updateHeight() {
            // Height is handled by CSS classes on the panel
        }
    }));
});
</script>