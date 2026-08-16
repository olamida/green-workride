{{-- BottomSheet: expandable sliding panel with drag handle --}}
@props([
    'id' => 'bottom-sheet',
    'title' => '',
    'halfHeight' => true,
    'fullHeight' => false,
])

<div
    x-data="{
        isOpen: {{ $halfHeight ? 'true' : 'false' }},
        isFull: {{ $fullHeight ? 'true' : 'false' }},
        startY: 0,
        currentY: 0,
        handleDragStart(e) {
            this.startY = e.touches ? e.touches[0].clientY : e.clientY;
            this.currentY = this.startY;
            window.addEventListener('mousemove', this.handleDragMove);
            window.addEventListener('touchmove', this.handleDragMove, { passive: true });
            window.addEventListener('mouseup', this.handleDragEnd);
            window.addEventListener('touchend', this.handleDragEnd);
        },
        handleDragMove(e) {
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            const delta = this.startY - clientY;
            // Constrain between fully open (top) and half height (bottom)
            const maxDrag = this.$refs.sheet.offsetHeight - 60; // keep handle visible
            this.currentY = Math.max(0, Math.min(maxDrag, delta));
            this.$refs.sheet.style.transform = `translateY(${this.currentY}px)`;
        },
        handleDragEnd() {
            const threshold = this.$refs.sheet.offsetHeight * 0.3;
            if (this.currentY > threshold) {
                this.isFull = true;
                this.$refs.sheet.style.transform = 'translateY(0)';
            } else {
                this.isFull = false;
                this.$refs.sheet.style.transform = 'translateY(0)';
            }
            window.removeEventListener('mousemove', this.handleDragMove);
            window.removeEventListener('touchmove', this.handleDragMove);
            window.removeEventListener('mouseup', this.handleDragEnd);
            window.removeEventListener('touchend', this.handleDragEnd);
        },
        toggle() {
            this.isFull = !this.isFull;
            this.$refs.sheet.style.transform = 'translateY(0)';
        }
    }"
    id="{{ $id }}"
    class="fixed bottom-0 left-0 right-0 z-50 transition-transform duration-300 ease-out"
    @class([
        'h-[50vh]' => ! $fullHeight && $halfHeight,
        'h-[85vh]' => $fullHeight || ! $halfHeight,
    ])
    x-ref="sheet"
    style="transform: translateY(0);"
>
    {{-- Drag handle --}}
    <div
        class="flex items-center justify-center h-12 bg-white border-t border-ink-200 rounded-t-2xl cursor-grab active:cursor-grabbing"
        @mousedown="handleDragStart"
        @touchstart.passive="handleDragStart"
        role="button"
        tabindex="0"
        aria-label="{{ $fullHeight ? 'Collapse sheet' : 'Expand sheet' }}"
        @keydown.space.prevent="toggle"
        @keydown.enter.prevent="toggle"
    >
        <div class="h-1 w-10 rounded-full bg-ink-200" aria-hidden="true"></div>
    </div>

    {{-- Content --}}
    <div class="overflow-y-auto bg-white rounded-t-2xl pb-safe">
        @if ($title)
            <div class="px-6 py-4 border-b border-ink-100">
                <h2 class="font-heading text-lg font-semibold text-ink-900">{{ $title }}</h2>
            </div>
        @endif

        <div class="p-6">
            {{ $slot }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Global bottom sheet state for cross-component communication
    window.BottomSheet = {
        open(id = 'bottom-sheet') {
            const el = document.getElementById(id);
            if (el && el.__x) {
                el.__x.$data.isOpen = true;
            }
        },
        close(id = 'bottom-sheet') {
            const el = document.getElementById(id);
            if (el && el.__x) {
                el.__x.$data.isOpen = false;
            }
        },
        toggle(id = 'bottom-sheet') {
            const el = document.getElementById(id);
            if (el && el.__x) {
                el.__x.$data.isFull = !el.__x.$data.isFull;
            }
        }
    };
</script>
@endpush