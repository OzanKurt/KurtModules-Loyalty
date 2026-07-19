// Live-update transport. Default: polling the /state endpoint (dependency-free).
// An Echo/broadcast driver can be swapped in by passing a different channel to
// LoyaltyCard; both only need { start(), stop() } and an onUpdate callback.

export class PollChannel {
    constructor(url, { interval = 5000, onUpdate = null, fetchImpl = null } = {}) {
        this.url = url;
        this.interval = interval;
        this.onUpdate = onUpdate;
        this.fetch = fetchImpl || (typeof fetch !== 'undefined' ? fetch.bind(globalThis) : null);
        this.timer = null;
    }

    async poll() {
        if (!this.fetch || !this.url) return;
        try {
            const res = await this.fetch(this.url, { headers: { Accept: 'application/json' } });
            if (res && res.ok) {
                const data = await res.json();
                if (this.onUpdate) this.onUpdate(data);
            }
        } catch {
            // Transient network error — keep polling.
        }
    }

    start() {
        if (this.timer) return this;
        this.timer = setInterval(() => this.poll(), this.interval);
        this.poll();
        return this;
    }

    stop() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
        return this;
    }
}
