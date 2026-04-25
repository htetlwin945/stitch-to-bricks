# Phase 0 Results

## Status

- Stack boots successfully with `docker compose up --build`
- WordPress responds on `http://localhost:8080`
- The plugin is active in the local WordPress install
- The new PHP Stitch client is working against real Stitch data
- Manual Bricks builder validation is partially complete

## Environment Checks

- `plugin_active=yes`
- `stitch_api_key_present=yes`
- `stitch_host=https://stitch.googleapis.com/mcp`
- PHP client successfully listed real Stitch projects and screens from WordPress

## Automated Payload Findings

Sampled screens:

1. `STB AI Hero Section` -> `Nexus AI Hero Section`
2. `STB AI Hero Section` -> `Clean Light Hero Section`
3. `Creative Agency Boilerplate (Desktop)` -> `Agency Services Showcase Section`
4. `Creative Agency Boilerplate (Desktop)` -> `Creative Agency Desktop Homepage`

Observed shape across the sample:

- All sampled payloads were full HTML documents with `<!DOCTYPE html>`
- HTML length ranged from roughly `9k` to `18k` chars
- All sampled payloads included `2` script tags
- All sampled payloads included `3` stylesheet links
- Some sampled payloads included inline `<style>` blocks, some did not
- Sampled payloads had `0` detected CSS custom property declarations (`--token:`)

## Preliminary Conclusions

- The PHP Stitch client can replace the Node proxy for the read flows tested so far: project list, screen list, and screen HTML fetch.
- Manual validation indicates `Body HTML` works better than full-document HTML as the default Bricks native-import payload.
- `Body HTML` remains the strongest candidate because sampled Stitch payloads are full documents with head-level scripts and stylesheet links.
- Sanitization is likely needed before the final native handoff flow because sampled payloads consistently include scripts and external stylesheets.
- A separate CSS handoff may still need testing manually in Bricks, but the sampled payloads do not currently expose useful `:root` variables in inline CSS.

## Finalized Contract

- Default handoff: `HTML only`
- Default payload: sanitized `Body HTML`
- Optional fallback: `Inline CSS` as a manual second paste step for screens that still need styling help

Sanitization rules now applied in the builder UI:

- The default `Body HTML` payload strips `script`, `style`, `link[rel="stylesheet"]`, and `noscript` tags from the body fragment before copying.
- The plugin never auto-copies external stylesheet links into the default handoff path.
- The optional `Inline CSS` fallback only copies inline `<style>` contents and strips any `@import` rules.
- `Full HTML` remains available only for inspection and difficult-screen testing, not as the default handoff path.

## Still Pending

- Additional manual validation of whether some screens still benefit from the optional `Inline CSS` second step
- Final decision on whether any Bricks-side automation hook is worth using
