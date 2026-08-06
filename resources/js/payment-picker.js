/**
 * Payment picker for the trip booking form (guide STEP 3.4 / suggestion §7).
 *
 * Large tappable rows (44px+) with a checkmark selection, a single primary
 * "Confirm seat · ₦X" button, press feedback and a calm loading state. The
 * POST contract is unchanged (`payment_method=wallet|cash|subsidy_credit|
 * ride_credit`) so the booking service's atomic hold/capture flow is untouched
 * — this is presentation only.
 */
export default function paymentPicker(config) {
    const methods = {
        wallet: { label: 'Wallet', ok: Boolean(config.walletOk) },
        subsidy_credit: { label: 'Subsidy credits', ok: Boolean(config.subsidyOk) },
        cash: { label: 'Cash to driver', ok: true },
        ride_credit: { label: 'Ride credit', ok: Boolean(config.rideCreditOk) },
    };

    const selectable = Object.keys(methods).filter((key) => methods[key].ok);
    const defaultMethod = selectable[0] || 'cash';

    return {
        fare: Number(config.fare) || 0,
        isFree: Boolean(config.isFree),
        method: config.isFree ? 'wallet' : defaultMethod,
        submitting: false,

        pick(name) {
            if (methods[name] && methods[name].ok) {
                this.method = name;
            }
        },

        isPicked(name) {
            return this.method === name;
        },

        canSubmit() {
            return this.isFree || (this.method !== 'free' && methods[this.method] && methods[this.method].ok);
        },

        submitLabel() {
            if (this.isFree) {
                return 'Confirm free ride';
            }
            return `Confirm seat · ₦${this.fare.toLocaleString('en-NG')}`;
        },
    };
}
