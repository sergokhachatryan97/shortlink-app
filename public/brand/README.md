# Trastly Logo Asset Pack

Production-ready logo system for Trastly — a modern shortlink / trusted redirect SaaS platform.

## Asset Structure

```
public/brand/
├── trastly-logo.svg           # Main horizontal logo (icon + wordmark), full color
├── trastly-logo-dark.svg      # Dark version — use on light backgrounds
├── trastly-logo-light.svg     # Light version — use on dark/cosmic backgrounds
├── trastly-logo-mono-black.svg   # Monochrome black
├── trastly-logo-mono-white.svg   # Monochrome white
├── trastly-logo-compact.svg   # Compact horizontal — for narrow navbars
├── trastly-icon.svg           # Icon only, full color
├── trastly-icon-dark.svg      # Icon, dark — light backgrounds
├── trastly-icon-light.svg     # Icon, light — dark backgrounds
├── trastly-icon-mono-black.svg
├── trastly-icon-mono-white.svg
├── favicon.svg                # Square favicon (SVG)
├── favicon-16.png             # 16×16 favicon (generate via export script)
├── favicon-32.png             # 32×32 favicon
├── apple-touch-icon.png       # 180×180 for iOS
├── android-chrome-192x192.png # 192×192 for Android
├── android-chrome-512x512.png # 512×512 for Android
├── export-pngs.js             # Script to generate PNGs from favicon.svg
├── preview.html               # Visual preview of all variants
└── README.md                  # This file
```

## Which File to Use

| Context | File |
|---------|------|
| **Navbar (light page)** | `trastly-logo-dark.svg` or `trastly-logo-compact.svg` |
| **Navbar (dark/cosmic page)** | `trastly-logo-light.svg` |
| **Footer (light)** | `trastly-logo-dark.svg` or `trastly-logo-mono-black.svg` |
| **Footer (dark)** | `trastly-logo-light.svg` or `trastly-logo-mono-white.svg` |
| **Login / auth page** | `trastly-logo.svg` or `trastly-logo-light.svg` (on dark bg) |
| **Dark hero section** | `trastly-logo-light.svg` |
| **Favicon** | `favicon.svg` (modern browsers) + `favicon-32.png` (fallback) |
| **Apple Touch Icon** | `apple-touch-icon.png` |
| **Android / PWA** | `android-chrome-192x192.png`, `android-chrome-512x512.png` |
| **App icon, mobile** | `trastly-icon.svg` or PNG exports |

## Quick Usage Examples

### Navbar (light)
```html
<a href="/" class="navbar-brand">
  <img src="/brand/trastly-logo-dark.svg" alt="Trastly" height="32">
</a>
```

### Navbar (cosmic/dark)
```html
<a href="/" class="navbar-brand">
  <img src="/brand/trastly-logo-light.svg" alt="Trastly" height="32">
</a>
```

### Favicon (in `<head>`)
```html
<link rel="icon" type="image/svg+xml" href="/brand/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="/brand/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="/brand/apple-touch-icon.png">
```

### Login page (dark background)
```html
<img src="/brand/trastly-logo-light.svg" alt="Trastly" height="40">
```

### Footer
```html
<a href="/"><img src="/brand/trastly-logo-mono-black.svg" alt="Trastly" height="24"></a>
```

## Generating PNG Files

The SVG sources are the primary format. PNG exports are created from `favicon.svg`.

### Option 1: Node script (recommended)
```bash
npm install sharp --save-dev
node public/brand/export-pngs.js
```

### Option 2: Manual export (Inkscape)
```bash
inkscape favicon.svg -w 16 -h 16 -o favicon-16.png
inkscape favicon.svg -w 32 -h 32 -o favicon-32.png
inkscape favicon.svg -w 180 -h 180 -o apple-touch-icon.png
inkscape favicon.svg -w 192 -h 192 -o android-chrome-192x192.png
inkscape favicon.svg -w 512 -h 512 -o android-chrome-512x512.png
```

### Option 3: ImageMagick
```bash
convert -background none favicon.svg -resize 32x32 favicon-32.png
```

## Design Specs

- **Primary gradient**: `#6366f1` → `#8b5cf6` (indigo to violet)
- **Icon**: Geometric forward arrow in rounded square — suggests redirect/direction
- **Typography**: Bold sans-serif, system font stack (convert to outlines for maximum compatibility if needed)

## Preview

Open `preview.html` in a browser to inspect all logo variants on light and dark backgrounds.
