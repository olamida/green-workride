/**
 * Connect-guide Alpine shell.
 *
 * Owns the glass HUD presentation (overview card → active HUD → arrived /
 * missed terminal panels) and re-triggers the number tick whenever a live
 * distance/ETA update arrives from the map module. The map module stays
 * presentation-free and reports through the callbacks below.
 */
export default function connectGuideUI() {
    return {
        mode: 'overview', // overview → active → arrived | missed
        status: '',
        distance: null,
        eta: null,
        missedReason: '',
        mapApi: null,
        voiceEnabled: false,
        voiceSupported: typeof window !== 'undefined' && 'speechSynthesis' in window,

        init() {
            const mapEl = this.$refs.map;
            if (!mapEl || !window.initConnectGuide) {
                return;
            }

            const target = JSON.parse(mapEl.dataset.target);
            this.status =
                target.type === 'none'
                    ? 'Waiting for the driver to share a location…'
                    : 'Pin the vehicle on the map, then walk to the green dot.';

            this.mapApi = window.initConnectGuide(mapEl, JSON.parse(mapEl.dataset.config), target, {
                onStatus: (text) => {
                    this.status = text;
                },
                onDistance: ({ distance, eta }) => {
                    this.distance = distance;
                    this.eta = eta;
                    this.$nextTick(() => this.tickNumbers());
                },
                onArrived: () => {
                    this.mode = 'arrived';
                    this.status = 'You have arrived — wave to the driver.';
                },
                onMissed: (reason) => {
                    this.missedReason = reason;
                    this.mode = 'missed';
                    this.status = reason;
                },
                onVoice: (text) => this.speak(text),
            });
        },

        start() {
            if (!this.mapApi) {
                return;
            }
            this.mode = 'active';
            this.mapApi.startFollow();
        },

        toggleVoice() {
            this.voiceEnabled = !this.voiceEnabled;
            if (!this.voiceEnabled && window.speechSynthesis) {
                window.speechSynthesis.cancel();
            }
        },

        // Optional spoken nudges — always opt-in, never on by default.
        speak(text) {
            if (!this.voiceEnabled || !this.voiceSupported || !text || !window.speechSynthesis) {
                return;
            }
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.rate = 1;
            window.speechSynthesis.speak(utterance);
        },

        tickNumbers() {
            [this.$refs.hudDistance, this.$refs.hudEta].forEach((el) => {
                if (!el) {
                    return;
                }
                el.classList.remove('wr-number-tick');
                void el.offsetWidth;
                el.classList.add('wr-number-tick');
            });
        },
    };
}
