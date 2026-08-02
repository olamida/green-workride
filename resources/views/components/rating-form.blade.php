@props(['action', 'title', 'cta' => 'Submit rating'])

<form method="POST" action="{{ $action }}" class="mt-4 space-y-3 rounded-xl border border-ink-100 bg-white p-4" x-data="{ rating: 0 }">
    @csrf

    <div>
        <p class="text-sm font-semibold text-ink-800">{{ $title }}</p>
        <div class="mt-2 flex items-center gap-1">
            <template x-for="star in [1, 2, 3, 4, 5]" :key="star">
                <button type="button" @click="rating = star" :aria-label="'Rate ' + star + ' star' + (star > 1 ? 's' : '')"
                        class="text-2xl leading-none transition-transform hover:scale-110"
                        :class="rating >= star ? 'text-gold-400' : 'text-ink-200'">★</button>
            </template>
            <span class="ml-2 text-xs text-ink-500" x-text="rating ? rating + ' / 5' : 'Tap to rate'"></span>
        </div>
        <input type="hidden" name="rating" :value="rating">
    </div>

    <textarea name="note" rows="2" maxlength="500" placeholder="Optional note — kept private to the operator"
              class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200"></textarea>

    <button type="submit" :disabled="! rating"
            class="w-full rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700 disabled:cursor-not-allowed disabled:opacity-40">
        {{ $cta }}
    </button>
</form>
