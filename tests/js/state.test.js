import { describe, expect, it } from 'vitest';
import { applyState, computeStamps } from '../../resources/js/state.js';

describe('computeStamps', () => {
    it('fills up to the count and leaves the rest empty', () => {
        expect(computeStamps(2, 4)).toEqual([
            { index: 1, state: 'filled' },
            { index: 2, state: 'filled' },
            { index: 3, state: 'empty' },
            { index: 4, state: 'empty' },
        ]);
    });

    it('returns an empty array for a zero requirement', () => {
        expect(computeStamps(3, 0)).toEqual([]);
    });
});

describe('applyState', () => {
    function makeRoot(required) {
        const root = document.createElement('div');
        root.setAttribute('data-loyalty-card', '');

        const count = document.createElement('span');
        count.setAttribute('data-loyalty-count', '');
        count.textContent = '0';
        root.append(count);

        const req = document.createElement('span');
        req.setAttribute('data-loyalty-required', '');
        req.textContent = '0';
        root.append(req);

        const list = document.createElement('ol');
        list.setAttribute('data-loyalty-stamps', '');
        for (let i = 1; i <= required; i++) {
            const li = document.createElement('li');
            li.setAttribute('data-loyalty-stamp', '');
            li.setAttribute('data-index', String(i));
            li.setAttribute('data-state', 'empty');
            list.append(li);
        }
        root.append(list);

        return root;
    }

    const states = (root) =>
        [...root.querySelectorAll('[data-loyalty-stamp]')].map((el) => el.getAttribute('data-state'));

    it('reflects counts and stamp states onto the DOM', () => {
        const root = makeRoot(3);
        applyState(root, {
            stamps_count: 2,
            is_complete: false,
            program: { stamps_required: 3 },
        });

        expect(root.querySelector('[data-loyalty-count]').textContent).toBe('2');
        expect(root.querySelector('[data-loyalty-required]').textContent).toBe('3');
        expect(states(root)).toEqual(['filled', 'filled', 'empty']);
        expect(root.hasAttribute('data-loyalty-complete')).toBe(false);
    });

    it('marks the root complete when the card is full', () => {
        const root = makeRoot(2);
        applyState(root, {
            stamps_count: 2,
            is_complete: true,
            program: { stamps_required: 2 },
        });

        expect(root.hasAttribute('data-loyalty-complete')).toBe(true);
        expect(states(root)).toEqual(['filled', 'filled']);
    });
});
