/**
 * Navigation-first home "Where are you going?" search box.
 *
 * Searches the read-only `/api/v1/navigation/search` endpoint (junctions +
 * workplaces first, then geocoded OSM fallback) and hands the chosen result to
 * the map so it can focus + pin + show a way there. The list stays the primary
 * path; the map reacts via the `destination-selected` window event.
 */
export function whereTo() {
    return {
        query: '',
        results: [],
        selected: null,
        searching: false,
        open: false,

        async search() {
            const q = this.query.trim();
            if (q.length < 2) {
                this.results = [];
                this.open = false;
                return;
            }

            this.searching = true;
            try {
                const params = new URLSearchParams({ q });
                if (Number.isFinite(window.workrideUser?.lat)) {
                    params.set('lat', window.workrideUser.lat);
                    params.set('lng', window.workrideUser.lng);
                }
                const res = await fetch(`/api/v1/navigation/search?${params}`);
                this.results = (await res.json()).data ?? [];
            } catch {
                this.results = [];
            } finally {
                this.searching = false;
                this.open = this.results.length > 0;
            }
        },

        select(result) {
            this.selected = result;
            this.results = [];
            this.open = false;
            this.query = result.name;
            window.dispatchEvent(new CustomEvent('destination-selected', { detail: result }));
        },

        reset() {
            this.selected = null;
            this.results = [];
            this.open = false;
            window.dispatchEvent(new CustomEvent('destination-cleared'));
        },
    };
}

if (typeof window !== 'undefined') {
    window.Alpine?.data('whereTo', whereTo);
}
