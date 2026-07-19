import '../css/contract.css';
import { LoyaltyCard } from './card.js';
import { LoyaltyTerminal } from './terminal.js';

export { LoyaltyCard, LoyaltyTerminal };
export { PollChannel } from './channel.js';
export { applyState, computeStamps } from './state.js';

/**
 * Boot every loyalty widget found within `scope`.
 * @returns {{cards: LoyaltyCard[], terminals: LoyaltyTerminal[]}}
 */
export function init(scope = document) {
    const cards = [];
    scope.querySelectorAll('[data-loyalty-card]').forEach((el) => {
        const card = new LoyaltyCard(el);
        card.init();
        cards.push(card);
    });

    const terminals = [];
    scope.querySelectorAll('[data-loyalty-terminal]').forEach((el) => {
        const terminal = new LoyaltyTerminal(el);
        terminal.init();
        terminals.push(terminal);
    });

    return { cards, terminals };
}

if (typeof window !== 'undefined') {
    window.Loyalty = { init, LoyaltyCard, LoyaltyTerminal };
    if (typeof document !== 'undefined') {
        if (document.readyState !== 'loading') init();
        else document.addEventListener('DOMContentLoaded', () => init());
    }
}
