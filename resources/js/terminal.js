// Drives [data-loyalty-terminal]: reads a scanned/typed card code and posts to
// the stamp or redeem endpoint, then shows the result. Camera scanning uses the
// native BarcodeDetector when available and degrades to manual entry otherwise.
export class LoyaltyTerminal {
    constructor(root, { fetchImpl = null } = {}) {
        this.root = root;
        this.fetch = fetchImpl || (typeof fetch !== 'undefined' ? fetch.bind(globalThis) : null);
        this.stampUrl = root.getAttribute('data-loyalty-stamp-url');
        this.redeemUrl = root.getAttribute('data-loyalty-redeem-url');
        this.input = root.querySelector('[data-loyalty-card-input]');
        this.result = root.querySelector('[data-loyalty-terminal-result]');
    }

    init() {
        const form = this.root.querySelector('[data-loyalty-terminal-form]');
        form?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.submit(this.stampUrl);
        });
        this.root.querySelector('[data-loyalty-redeem-btn]')?.addEventListener('click', () => {
            this.submit(this.redeemUrl);
        });
        return this;
    }

    async submit(url) {
        const token = this.input?.value?.trim();
        if (!token || !url || !this.fetch || this.busy) return null;

        this.setBusy(true);
        let ok = false;
        let data = {};
        try {
            const res = await this.fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Idempotency-Key': idempotencyKey(),
                    ...csrfHeader(),
                },
                body: JSON.stringify({ card_token: token }),
            });
            ok = res.ok;
            data = await res.json().catch(() => ({}));
        } catch {
            data = { message: 'Network error' };
        } finally {
            this.setBusy(false);
        }

        this.showResult(ok, data);
        return { ok, data };
    }

    setBusy(busy) {
        this.busy = busy;
        this.root.querySelectorAll('[data-loyalty-stamp-btn], [data-loyalty-redeem-btn]').forEach((btn) => {
            btn.disabled = busy;
        });
        this.root.toggleAttribute('data-loyalty-busy', busy);
    }

    showResult(ok, data) {
        if (!this.result) return;
        this.result.hidden = false;
        this.result.setAttribute('data-status', ok ? 'ok' : 'error');
        this.result.textContent = ok
            ? `${data.stamps_count ?? '?'} / ${data.program?.stamps_required ?? '?'}`
            : (data.message || 'Rejected');
    }
}

function csrfHeader() {
    if (typeof document === 'undefined') return {};
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? { 'X-CSRF-TOKEN': meta.getAttribute('content') } : {};
}

function idempotencyKey() {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) return crypto.randomUUID();
    return `k-${Date.now()}-${Math.round(Math.random() * 1e9)}`;
}
