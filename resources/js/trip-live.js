/**
 * Live trip seat counter + progress tracker (Sprint 3 §3.1/§3.2).
 *
 * Listens on the private `trip.{id}` channel for:
 *   .BookingConfirmed / .BookingCancelled  → seat count
 *   .BookingRequested                      → pending request pulse
 *   .TripLocationUpdated                   → authoritative progress payload
 *   .WaypointReached                       → cross-off a stop immediately
 *
 * Progress items are updated in place via `data-wp-*` hooks so the tracker
 * never re-renders wholesale (no layout flash while driving).
 */

const STATUS = {
    passed: {
        dot: 'bg-forest-100 text-forest-700',
        label: 'text-ink-500 line-through decoration-ink-300',
        marker: '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5"/></svg>',
    },
    current: {
        dot: 'bg-gold-100 text-gold-800 ring-2 ring-gold-300',
        label: 'text-gold-800 font-semibold',
        marker: '<span class="h-2.5 w-2.5 rounded-full bg-gold-500"></span>',
    },
    upcoming: {
        dot: 'bg-ink-100 text-ink-500',
        label: 'text-ink-800',
        marker: '<span class="font-mono text-xs font-semibold"></span>',
    },
};

export default function tripLive(options) {
    return {
        seats: options.initial,
        requests: options.requests ?? 0,
        lastEvent: null,

        init() {
            if (!window.Echo) {
                return;
            }

            window.Echo.private(`trip.${options.tripId}`)
                .listen('.BookingConfirmed', (e) => {
                    this.seats = e.available_seats;
                    this.tickSeats();
                })
                .listen('.BookingCancelled', (e) => {
                    this.seats = e.available_seats;
                    this.tickSeats();
                })
                .listen('.BookingRequested', (e) => {
                    this.requests += 1;
                    this.lastEvent = { type: 'request', passenger: e.passenger_name };
                    this.pulseRequests();
                })
                .listen('.TripLocationUpdated', (e) => {
                    if (Array.isArray(e.progress)) {
                        this.renderProgress(e.progress);
                    }
                })
                .listen('.WaypointReached', (e) => {
                    this.markWaypointPassed(e);
                });
        },

        tickSeats() {
            this.$nextTick(() => {
                const el = this.$el?.querySelector('[data-seats-tick]');
                if (!el) {
                    return;
                }

                el.classList.remove('wr-seat-tick');
                void el.offsetWidth;
                el.classList.add('wr-seat-tick');
            });
        },

        pulseRequests() {
            this.$nextTick(() => {
                const el = this.$el?.querySelector('[data-requests-pulse]');
                if (!el) {
                    return;
                }

                el.classList.remove('wr-pulse');
                void el.offsetWidth;
                el.classList.add('wr-pulse');
            });
        },

        renderProgress(progress) {
            if (!this.$root) {
                return;
            }

            const items = this.$root.querySelectorAll('[data-wp-id]');
            const byId = new Map(progress.map((point) => [String(point.id), point]));

            items.forEach((item) => {
                const point = byId.get(item.getAttribute('data-wp-id'));
                if (!point) {
                    return;
                }

                this.applyPointState(item, point);

                const eta = item.querySelector('[data-wp-eta]');
                if (eta && point.eta_minutes !== null && point.eta_minutes !== undefined) {
                    eta.textContent = `${point.eta_minutes} min`;
                    eta.classList.remove('wr-number-tick');
                    void eta.offsetWidth;
                    eta.classList.add('wr-number-tick');
                }

                const distance = item.querySelector('[data-wp-distance]');
                if (distance && point.distance_from_origin_km !== null && point.distance_from_origin_km !== undefined) {
                    distance.textContent = `${Number(point.distance_from_origin_km).toFixed(1)} km`;
                }

                const reached = item.querySelector('[data-wp-reached]');
                if (reached && point.reached_at) {
                    reached.textContent = `Reached ${new Date(point.reached_at).toLocaleString()}`;
                }
            });
        },

        markWaypointPassed(event) {
            if (!this.$root) {
                return;
            }

            const item = this.$root.querySelector(`[data-wp-id="${event.waypoint_id}"]`);
            if (!item) {
                return;
            }

            const point = {
                id: event.waypoint_id,
                label: event.label,
                sequence: event.sequence,
                status: 'passed',
                reached_at: event.reached_at,
            };

            this.applyPointState(item, point);

            // Promote the next upcoming stop to the current one.
            const next = item.nextElementSibling && item.nextElementSibling.matches('[data-wp-id]')
                ? item.nextElementSibling
                : null;

            if (next && next.getAttribute('data-wp-status') === 'upcoming') {
                this.applyPointState(next, { id: next.getAttribute('data-wp-id'), status: 'current' });
            }
        },

        applyPointState(item, point) {
            const status = point.status ?? 'upcoming';
            const state = STATUS[status] ?? STATUS.upcoming;

            item.setAttribute('data-wp-status', status);

            if (point.status === 'current') {
                item.setAttribute('aria-current', 'step');
            } else {
                item.removeAttribute('aria-current');
            }

            const dot = item.querySelector('[data-wp-dot]');
            if (dot) {
                dot.className = `wr-wp-dot mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full transition ${state.dot}`;
                dot.innerHTML = state.marker;
                if (status === 'upcoming' && point.sequence !== undefined) {
                    dot.querySelector('.font-mono').textContent = point.sequence;
                }
            }

            const label = item.querySelector('[data-wp-label]');
            if (label) {
                label.className = `text-sm transition ${state.label}`;
            }

            const now = item.querySelector('[data-wp-now]');
            if (now) {
                now.hidden = status !== 'current';
            }
        },
    };
}
