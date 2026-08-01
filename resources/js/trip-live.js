export default function tripLive(options) {
    return {
        seats: options.initial,

        init() {
            if (!window.Echo) {
                return;
            }

            window.Echo.private(`trip.${options.tripId}`)
                .listen('.BookingConfirmed', (e) => {
                    this.seats = e.available_seats;
                })
                .listen('.BookingCancelled', (e) => {
                    this.seats = e.available_seats;
                });
        },
    };
}
