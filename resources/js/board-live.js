export default function boardLive() {
    return {
        live: false,

        init() {
            if (!window.Echo) {
                return;
            }

            const channel = window.Echo.channel('trips');

            channel.listen('.TripSeatsUpdated', (e) => {
                const card = document.querySelector(`[data-trip-card="${e.trip_id}"]`);
                if (card) {
                    const seats = card.querySelector('[data-seats]');
                    if (seats) {
                        seats.textContent = `${e.available_seats}/${e.total_seats}`;
                        seats.classList.remove('wr-seat-tick');
                        // Re-trigger the one-shot highlight (restart the animation).
                        void seats.offsetWidth;
                        seats.classList.add('wr-seat-tick');
                    }

                    const full = card.querySelector('[data-seats-full]');
                    if (full) {
                        full.classList.toggle('hidden', e.available_seats > 0);
                    }

                    const bookBtn = card.querySelector('[data-book-link]');
                    if (bookBtn) {
                        bookBtn.classList.toggle('opacity-50', e.available_seats < 1);
                        bookBtn.classList.toggle('pointer-events-none', e.available_seats < 1);
                    }
                }

                if (window.__tripsMap) {
                    window.__tripsMap.updateTripSeats(e.trip_id, e.available_seats, e.total_seats);
                }
            });

            channel.listen('.TripPublished', (e) => {
                if (!document.querySelector(`[data-trip-card="${e.id}"]`)) {
                    this.live = true;
                }
            });
        },
    };
}
