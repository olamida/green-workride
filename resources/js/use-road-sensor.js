export default function useRoadSensor(options) {
    const threshold = options.threshold ?? 15;
    const endpoint = options.endpoint ?? '/api/v1/road-events';
    const throttleMs = options.throttleMs ?? 30000;
    const tripId = options.tripId ?? null;

    return {
        status: 'idle',
        hits: 0,
        lastSentAt: 0,

        init() {
            if (!('DeviceMotionEvent' in window)) {
                this.status = 'unsupported';
                return;
            }

            if (typeof DeviceMotionEvent.requestPermission === 'function') {
                DeviceMotionEvent.requestPermission().catch(() => {
                    this.status = 'denied';
                });
            }

            window.addEventListener('devicemotion', (event) => this.onMotion(event));
            this.status = 'listening';
        },

        onMotion(event) {
            const acc = event.accelerationIncludingGravity;
            if (!acc) {
                return;
            }

            const z = Math.abs(acc.z ?? 0);

            if (z <= threshold) {
                return;
            }

            this.hits += 1;

            const now = Date.now();
            if (now - this.lastSentAt < throttleMs) {
                return;
            }
            this.lastSentAt = now;

            this.send(z);
        },

        async send(z) {
            if (!navigator.geolocation) {
                return;
            }

            navigator.geolocation.getCurrentPosition(async (position) => {
                try {
                    await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                            Accept: 'application/json',
                        },
                        body: JSON.stringify({
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                            type: 'pothole',
                            severity: Math.min(5, Math.max(1, Math.round(z / 15))),
                            speed: position.coords.speed,
                            accelerometer_z: z,
                            trip_id: tripId,
                        }),
                    });
                } catch {
                    // Sensor collection is best-effort; never block the ride.
                }
            });
        },
    };
}
