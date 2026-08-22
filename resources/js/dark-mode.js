// Dark mode toggle + system preference
export default () => ({
    isDark: false,
    init() {
        // Check localStorage first, then system preference
        const stored = localStorage.getItem('wr-theme');
        if (stored) {
            this.isDark = stored === 'dark';
        } else {
            this.isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        this.apply();

        // Listen for system preference changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('wr-theme')) {
                this.isDark = e.matches;
                this.apply();
            }
        });
    },
    apply() {
        document.documentElement.classList.toggle('dark', this.isDark);
        localStorage.setItem('wr-theme', this.isDark ? 'dark' : 'light');
        this.$dispatch('theme-changed', { isDark: this.isDark });
    },
    toggle() {
        this.isDark = !this.isDark;
        this.apply();
    }
});