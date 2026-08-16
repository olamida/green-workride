/**
 * Go Board Alpine components and Reverb live seat updates.
 *
 * Components:
 * - goBoard: Main board state (selected window, pull-to-refresh, map focus)
 * - whereTo: Destination search (reused from navigation)
 */

/**
 * Pull-to-refresh state machine
 */
function usePullToRefresh(onRefresh) {
    let startY = 0;
    let currentY = 0;
    let pulling = false;
    const threshold = 80;

    function onTouchStart(e) {
        if (window.scrollY === 0) {
            startY = e.touches[0].clientY;
            pulling = true;
        }
    }

    function onTouchMove(e) {
        if (!pulling) return;
        currentY = e.touches[0].clientY;
        const delta = currentY - startY;
        if (delta > 0) {
            e.preventDefault();
            document.documentElement.style.setProperty('--pull-distance', `${Math.min(delta, threshold * 1.5)}px`);
        }
    }

    function onTouchEnd() {
        if (!pulling) return;
        pulling = false;
        const delta = currentY - startY;
        document.documentElement.style.setProperty('--pull-distance', '0px');
        if (delta > threshold) {
            onRefresh();
        }
    }

    document.addEventListener('touchstart', onTouchStart, { passive: true });
    document.addEventListener('touchmove', onTouchMove, { passive: false });
    document.addEventListener('touchend', onTouchEnd);

    return () => {
        document.removeEventListener('touchstart', onTouchStart);
        document.removeEventListener('touchmove', onTouchMove);
        document.removeEventListener('touchend', onTouchEnd);
    };
}

/**
 * Go Board Alpine data
 */
window.Alpine?.data('goBoard', () => ({
    selectedWindow: 'now',
    mapFocused: false,
    rideCardsContainer: null,

    init() {
        this.rideCardsContainer = this.$refs.rideCards;
        this.setupPullToRefresh();
    },

    setupPullToRefresh() {
        usePullToRefresh(() => this.refreshBoard());
    },

    async refreshBoard() {
        try {
            const res = await fetch(`/api/v1/go/board?window=${this.selectedWindow}`);
            const data = await res.json();
            this.updateBoard(data);
        } catch (e) {
            console.error('Failed to refresh board:', e);
        }
    },

    updateBoard(data) {
        if (data.trips) {
            this.$dispatch('board-updated', { trips: data.trips });
        }
        if (data.demand) {
            this.$dispatch('demand-updated', { demand: data.demand });
        }
    },

    setWindow(window) {
        this.selectedWindow = window;
        this.refreshBoard();
    },

    onDestinationSelected(result) {
        this.mapFocused = true;
        if (window.__goMap) {
            window.__goMap.focusDestination(result);
        }
    },

    onDestinationCleared() {
        this.mapFocused = false;
        if (window.__goMap && window.__goMapConfig) {
            const config = window.__goMapConfig;
            window.__goMap.map.setView([config.cbd.lat, config.cbd.lng], config.default_zoom);
        }
    },
}));

/**
 * Initialize Reverb live seat updates for the Go Board
 */
window.initGoBoardLive = function() {
    if (!window.Echo) {
        return;
    }

    // Listen on public trips channel for TripSeatsUpdated
    window.Echo.channel('trips')
        .listen('.TripSeatsUpdated', (e) => {
            if (window.__goMap) {
                window.__goMap.updateTripSeats(e.trip_id, e.available_seats, e.total_seats);
            }

            // Also update the bottom sheet cards
            const cards = document.querySelectorAll(`[data-trip-card="${e.trip_id}"]`);
            cards.forEach((card) => {
                const seatEl = card.querySelector('[data-seats]');
                if (seatEl) {
                    seatEl.textContent = `${e.available_seats}/${e.total_seats}`;
                    seatEl.classList.add('wr-seat-tick');
                    setTimeout(() => seatEl.classList.remove('wr-seat-tick'), 300);
                }
                const fullEl = card.querySelector('[data-seats-full]');
                if (fullEl && e.available_seats === 0) {
                    fullEl.classList.remove('hidden');
                } else if (fullEl) {
                    fullEl.classList.add('hidden');
                }
            });
        })
        .listen('.TripPublished', (e) => {
            // New trip published - could add to map and list
            if (window.__goMap) {
                // The map will need the full trip data to add a pin
                // For now, trigger a board refresh
                window.dispatchEvent(new CustomEvent('go-board-refresh'));
            }
        });

    // Request user location for the map
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                if (window.__goMap) {
                    window.__goMap.setUserLocation(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy);
                }
            },
            (err) => console.warn('Geolocation denied:', err),
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
        );
    }
};

/**
 * whereTo search component (reused from navigation)
 */
window.Alpine?.data('whereTo', () => ({
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
        this.query = '';
        window.dispatchEvent(new CustomEvent('destination-cleared'));
    },
}));