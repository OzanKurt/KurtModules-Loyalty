import QRCode from 'qrcode';

// Render a QR code into [data-loyalty-qr]. Kept isolated so the (optional)
// qrcode dependency is easy to tree-shake or replace.
export async function renderQr(el, value, { width = 220, margin = 1 } = {}) {
    if (!el || !value) return;
    try {
        const canvas = document.createElement('canvas');
        await QRCode.toCanvas(canvas, String(value), { width, margin });
        el.replaceChildren(canvas);
    } catch {
        // Fall back to the raw code text if rendering fails.
        el.textContent = String(value);
    }
}
