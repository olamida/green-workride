/**
 * ⌘K command palette — Linear/Stripe-style quick launcher.
 *
 * Reads its items from a JSON blob rendered server-side (#wr-command-data),
 * so every route + quick action stays a plain link/form. Keyboard-first:
 * Ctrl/⌘+K to open, arrows to move, Enter to run, Esc to close.
 */
export default function commandPalette() {
    return {
        open: false,
        query: '',
        index: 0,
        items: [],
        init() {
            const el = this.$el.querySelector('#wr-command-data');
            this.items = el ? JSON.parse(el.textContent) : [];

            this.$watch('query', () => {
                this.index = 0;
            });

            this.$watch('open', (val) => {
                if (val) {
                    this.$nextTick(() => this.$refs.input?.focus());
                    this.query = '';
                    this.index = 0;
                }
            });

            window.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                    e.preventDefault();
                    this.open = !this.open;
                }
                if (e.key === 'Escape' && this.open) {
                    this.open = false;
                }
            });

            // Header "Search… ⌘K" button → opens the palette.
            window.addEventListener('open-command', () => {
                this.open = true;
            });
        },
        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (!q) {
                return this.items;
            }
            return this.items.filter((item) =>
                `${item.label} ${item.group} ${item.keywords ?? ''}`.toLowerCase().includes(q),
            );
        },
        move(dir) {
            const count = this.filtered.length;
            if (!count) {
                return;
            }
            this.index = (this.index + dir + count) % count;
        },
        go() {
            const item = this.filtered[this.index];
            if (!item) {
                return;
            }
            this.open = false;
            if (item.action === 'submit') {
                document.getElementById(item.form)?.submit();
            } else if (item.url) {
                window.location.href = item.url;
            }
        },
    };
}
