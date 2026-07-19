// Copies the raw theme stylesheets into the built dist/ so a plain
// `<link>` can load them alongside the bundled loyalty.css (contract).
import { cp, mkdir } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const from = resolve(root, 'resources/css/themes');
const to = resolve(root, 'resources/dist/themes');

await mkdir(to, { recursive: true });
await cp(from, to, { recursive: true });

console.log('Copied themes ->', to);
