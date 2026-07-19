// Pure state helpers — no DOM, no framework. Unit-tested in isolation.

/**
 * Build the per-stamp state array from a count + requirement.
 * @returns {{index:number,state:'filled'|'empty'}[]}
 */
export function computeStamps(count, required) {
    const stamps = [];
    for (let i = 1; i <= required; i++) {
        stamps.push({ index: i, state: i <= count ? 'filled' : 'empty' });
    }
    return stamps;
}

/**
 * Apply a card state object to a card root element by toggling the
 * data-attribute contract. Never touches classes or colors — CSS owns visuals.
 */
export function applyState(root, state) {
    if (!root || !state) return root;

    const required = state.program ? state.program.stamps_required : 0;

    const countEl = root.querySelector('[data-loyalty-count]');
    if (countEl) countEl.textContent = String(state.stamps_count ?? 0);

    const requiredEl = root.querySelector('[data-loyalty-required]');
    if (requiredEl) requiredEl.textContent = String(required);

    const computed = state.stamps && state.stamps.length
        ? state.stamps
        : computeStamps(state.stamps_count ?? 0, required);

    root.querySelectorAll('[data-loyalty-stamp]').forEach((el) => {
        const index = Number(el.getAttribute('data-index'));
        const match = computed.find((s) => s.index === index);
        if (match) el.setAttribute('data-state', match.state);
    });

    root.toggleAttribute('data-loyalty-complete', Boolean(state.is_complete));

    return root;
}
