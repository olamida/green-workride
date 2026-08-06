<button
    type="button"
    x-show="voiceSupported"
    x-cloak
    x-on:click="toggleVoice()"
    :aria-pressed="voiceEnabled ? 'true' : 'false'"
    :title="voiceEnabled ? 'Turn voice announcements off' : 'Turn voice announcements on'"
    :class="voiceEnabled ? 'border border-forest-600 bg-forest-600 text-white' : 'border border-ink-200 bg-white text-ink-700 hover:bg-forest-50'"
    class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center gap-2 rounded-xl px-3 text-xs font-semibold transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest-600"
>
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M11 5 6 9H2v6h4l5 4V5Z"></path>
        <path x-show="voiceEnabled" d="M15.5 8.5a5 5 0 0 1 0 7"></path>
        <path x-show="voiceEnabled" d="M18.5 5.5a9 9 0 0 1 0 13"></path>
    </svg>
    <span class="hidden sm:inline" x-text="voiceEnabled ? 'Voice on' : 'Voice'"></span>
    <span class="sr-only" x-text="voiceEnabled ? 'Turn voice announcements off' : 'Turn voice announcements on'"></span>
</button>
