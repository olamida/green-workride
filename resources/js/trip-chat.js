export default function tripChat(options) {
    return {
        tripId: options.tripId,
        messages: options.messages ?? [],
        message: '',
        sending: false,

        init() {
            if (!options.canChat || !window.Echo) {
                return;
            }

            window.Echo.private(`trip.${this.tripId}`)
                .listen('.NewChatMessage', (e) => {
                    if (Number(e.sender_id) === Number(window.App.userId)) {
                        return;
                    }

                    this.messages.push(e);
                    this.scrollDown();
                });
        },

        async send() {
            const text = this.message.trim();

            if (!text || this.sending) {
                return;
            }

            this.sending = true;

            try {
                const response = await fetch(`/trips/${this.tripId}/messages`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ message: text }),
                });

                const data = await response.json();

                if (response.ok && data.chat) {
                    this.messages.push(data.chat);
                    this.message = '';
                    this.scrollDown();
                }
            } finally {
                this.sending = false;
            }
        },

        scrollDown() {
            this.$nextTick(() => {
                const el = this.$refs.messages;

                if (el) {
                    el.scrollTop = el.scrollHeight;
                }
            });
        },
    };
}
