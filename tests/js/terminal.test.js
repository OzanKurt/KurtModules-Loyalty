import { beforeEach, describe, expect, it, vi } from 'vitest';
import { LoyaltyTerminal } from '../../resources/js/terminal.js';

function makeTerminal() {
    const root = document.createElement('div');
    root.setAttribute('data-loyalty-terminal', '');
    root.setAttribute('data-loyalty-stamp-url', '/loyalty/terminal/stamp');
    root.setAttribute('data-loyalty-redeem-url', '/loyalty/terminal/redeem');

    const form = document.createElement('form');
    form.setAttribute('data-loyalty-terminal-form', '');
    const input = document.createElement('input');
    input.setAttribute('data-loyalty-card-input', '');
    form.append(input);
    root.append(form);

    const result = document.createElement('div');
    result.setAttribute('data-loyalty-terminal-result', '');
    result.hidden = true;
    root.append(result);

    return { root, input, result };
}

describe('LoyaltyTerminal', () => {
    let root;
    let input;
    let result;

    beforeEach(() => {
        ({ root, input, result } = makeTerminal());
    });

    it('posts the card token to the stamp endpoint and shows progress', async () => {
        const fetchImpl = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ stamps_count: 1, program: { stamps_required: 7 } }),
        });

        const terminal = new LoyaltyTerminal(root, { fetchImpl }).init();
        input.value = 'ab865d70';
        const outcome = await terminal.submit(terminal.stampUrl);

        expect(fetchImpl).toHaveBeenCalledOnce();
        const [url, options] = fetchImpl.mock.calls[0];
        expect(url).toBe('/loyalty/terminal/stamp');
        expect(JSON.parse(options.body)).toEqual({ card_token: 'ab865d70' });
        expect(outcome.ok).toBe(true);
        expect(result.hidden).toBe(false);
        expect(result.textContent).toBe('1 / 7');
        expect(result.getAttribute('data-status')).toBe('ok');
    });

    it('shows the error message on a rejected request', async () => {
        const fetchImpl = vi.fn().mockResolvedValue({
            ok: false,
            json: async () => ({ message: 'Cooldown not elapsed.' }),
        });

        const terminal = new LoyaltyTerminal(root, { fetchImpl }).init();
        input.value = 'ab865d70';
        await terminal.submit(terminal.stampUrl);

        expect(result.getAttribute('data-status')).toBe('error');
        expect(result.textContent).toBe('Cooldown not elapsed.');
    });

    it('does nothing when no token is entered', async () => {
        const fetchImpl = vi.fn();
        const terminal = new LoyaltyTerminal(root, { fetchImpl }).init();
        input.value = '   ';
        const outcome = await terminal.submit(terminal.stampUrl);

        expect(fetchImpl).not.toHaveBeenCalled();
        expect(outcome).toBeNull();
    });
});
