/**
 * Multi-step onboarding wizard (Sprint 3 §3.3). Owns a linear step state
 * machine; the panel body is rendered by the host component and revealed with
 * `x-show="isCurrent('step-key')"`. Steps can jump backwards freely (editing)
 * but forwards only via next(). Reduced-motion users get instant panel scroll.
 */
export default function progressWizard(options) {
    return {
        steps: options.steps,
        step: options.initial,

        lat: options.lat ?? '',
        lng: options.lng ?? '',
        locationStatus: '',

        corridor: options.corridor ?? '',
        isFreeVolunteer: options.isFreeVolunteer ?? false,
        corridorLabels: options.corridorLabels ?? {},

        repeat: options.repeat ?? false,

        locate() {
            if (!navigator.geolocation) {
                this.locationStatus = 'Geolocation not supported by this browser.';
                return;
            }
            this.locationStatus = 'Locating…';
            navigator.geolocation.getCurrentPosition((position) => {
                this.lat = position.coords.latitude.toFixed(7);
                this.lng = position.coords.longitude.toFixed(7);
                this.locationStatus = 'Location set. Publish from here to appear on the board.';
            }, () => {
                this.locationStatus = 'Could not get your location. You can still publish without it.';
            });
        },

        go(key) {
            if (!this.steps.includes(key) || key === this.step) {
                return;
            }

            this.step = key;

            this.$nextTick(() => {
                const panel = this.$refs.panel;
                if (!panel) {
                    return;
                }

                const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                panel.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'nearest' });
            });
        },

        next() {
            const index = this.steps.indexOf(this.step);

            if (index >= 0 && index < this.steps.length - 1) {
                this.go(this.steps[index + 1]);
            }
        },

        back() {
            const index = this.steps.indexOf(this.step);

            if (index > 0) {
                this.go(this.steps[index - 1]);
            }
        },

        isCurrent(key) {
            return this.step === key;
        },

        isDone(key) {
            return this.steps.indexOf(key) < this.steps.indexOf(this.step);
        },

        stepNumber(key) {
            return this.steps.indexOf(key) + 1;
        },

        isFirst() {
            return this.steps.indexOf(this.step) === 0;
        },

        isLast() {
            return this.steps.indexOf(this.step) === this.steps.length - 1;
        },
    };
}
