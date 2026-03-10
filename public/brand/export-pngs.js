#!/usr/bin/env node
/**
 * Export Trastly logo SVGs to PNG at required sizes.
 * Requires: npm install sharp --save-dev
 * Run: node public/brand/export-pngs.js
 */
import { readFileSync, existsSync } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const brandDir = __dirname;

const sizes = [
  { name: 'favicon-16', size: 16 },
  { name: 'favicon-32', size: 32 },
  { name: 'apple-touch-icon', size: 180 },
  { name: 'android-chrome-192x192', size: 192 },
  { name: 'android-chrome-512x512', size: 512 },
];

async function exportPngs() {
  let sharp;
  try {
    sharp = (await import('sharp')).default;
  } catch (e) {
    console.log('Installing sharp... run: npm install sharp --save-dev');
    console.log('Then run this script again.');
    process.exit(1);
  }

  const svgPath = join(brandDir, 'favicon.svg');
  if (!existsSync(svgPath)) {
    console.error('favicon.svg not found');
    process.exit(1);
  }

  const svg = readFileSync(svgPath);

  for (const { name, size } of sizes) {
    const outPath = join(brandDir, `${name}.png`);
    try {
      await sharp(svg)
        .resize(size, size)
        .png()
        .toFile(outPath);
      console.log(`Created ${name}.png (${size}x${size})`);
    } catch (err) {
      console.error(`Failed ${name}:`, err.message);
    }
  }
  console.log('Done.');
}

exportPngs();
