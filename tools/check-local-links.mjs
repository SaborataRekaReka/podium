import { readFileSync, existsSync } from 'node:fs';
import { resolve, dirname } from 'node:path';

const root = process.cwd();
const htmlPath = resolve(root, 'index.html');
const html = readFileSync(htmlPath, 'utf8');

const attrPattern = /(?:src|href)=\"([^\"]+)\"/g;
const refs = [];
let match;
while ((match = attrPattern.exec(html)) !== null) {
  const ref = match[1];
  if (!ref || ref.startsWith('http://') || ref.startsWith('https://') || ref.startsWith('#') || ref.startsWith('mailto:') || ref.startsWith('tel:')) continue;
  refs.push(ref);
}

const missing = [];
for (const ref of refs) {
  const withoutQuery = ref.split('?')[0].split('#')[0];
  const normalized = withoutQuery.startsWith('/') ? withoutQuery.slice(1) : withoutQuery;
  let decoded = normalized;
  try { decoded = decodeURIComponent(normalized); } catch {}
  const full = resolve(dirname(htmlPath), decoded);
  if (!existsSync(full)) {
    missing.push(ref);
  }
}

if (missing.length) {
  console.error('Missing local links/assets found:');
  for (const item of missing) console.error(` - ${item}`);
  process.exit(1);
}

console.log(`OK: checked ${refs.length} local src/href references in index.html`);
