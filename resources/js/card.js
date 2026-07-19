import { PollChannel } from './channel.js';
import { applyState } from './state.js';

// Drives a single [data-loyalty-card] element: seeds from the embedded config,
// renders the QR, applies live state, and animates newly-added stamps.
export class LoyaltyCard {
    constructor(root, { channelFactory = null } = {}) {
        this.root = root;
        this.channelFactory = channelFactory;
        this.config = this.readConfig();
        this.channel = null;
    }

    readConfig() {
        const el = this.root.querySelector('[data-loyalty-config]');
        if (!el) return {};
        try {
            return JSON.parse(el.textContent);
        } catch {
            return {};
        }
    }

    async init() {
        applyState(this.root, this.config);
        this.revealWalletActions();

        const url = this.root.getAttribute('data-loyalty-state-url');
        if (url) {
            this.channel = this.channelFactory
                ? this.channelFactory(url, (s) => this.onUpdate(s))
                : new PollChannel(url, { onUpdate: (s) => this.onUpdate(s) });
            this.channel.start();
        }

        return this;
    }

    onUpdate(state) {
        const before = this.config.stamps_count ?? 0;
        this.config = state;
        applyState(this.root, state);
        if ((state.stamps_count ?? 0) > before) this.pulse();
    }

    pulse() {
        this.root.setAttribute('data-loyalty-pulse', '');
        setTimeout(() => this.root.removeAttribute('data-loyalty-pulse'), 700);
    }

    revealWalletActions() {
        const links = this.root.querySelectorAll('[data-loyalty-wallet]');
        links.forEach((link) => {
            if (link.getAttribute('href')) {
                link.hidden = false;
                const box = link.closest('[data-loyalty-wallet-actions]');
                if (box) box.hidden = false;
            }
        });
    }

    destroy() {
        this.channel?.stop();
    }
}
