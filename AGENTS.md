# AGENTS.md

## Repo Shape
- This repo is a WordPress plugin, not a Composer app. `stitch-to-bricks.php` is the manual bootstrap and loads runtime classes from `includes/`.
Runtime pieces:
- `stitch-to-bricks.php`: minimal plugin bootstrap.
- `includes/class-stb-ai-client.php`: OpenAI API wrapper with Vision support, usage tracking, and dynamic prompt generation.
- `includes/class-stb-design-manager.php`: Custom Post Type (`stb_design`) manager for saving, loading, and versioning AI-generated designs.
- `includes/class-stb-ai-designer-ui.php`: Admin UI for the AI Designer, including chat interface and sandboxed preview.
- `includes/class-stb-settings.php`: Expanded settings page for API keys, usage quotas, and **Design System Configuration** (DB-stored `DESIGN.md` and CSS).
- `includes/class-stb-builder-ui.php` + `assets/js/builder-integration.js`: Legacy Bricks toolbar button (still present for Stitch integration).
- `includes/class-stb-ajax-handler.php`: WordPress AJAX bridge for both Stitch browsing and AI generation/refinement.
- `assets/js/ai-designer.js`: Frontend logic for the AI chat, image upload, and preview updates.
- `assets/css/ai-designer.css`: Styling for the AI Designer interface.

## Commands
- Full local stack from repo root: `docker compose up --build`
- WordPress is exposed at `http://localhost:8080`.
- The repo is bind-mounted into the WordPress container at `/var/www/html/wp-content/plugins/stitch-to-bricks`, so PHP/asset edits are live.
- Stitch auth/config comes from the WordPress container env: `STITCH_API_KEY` and optional `STITCH_HOST`.

## Verified Gotchas
- **Design System Storage**: `DESIGN.md`, `core-framework.css`, and the cheatsheet are now stored in the WP database (`stb_design_md`, `stb_core_css`, `stb_cheatsheet`). They are served dynamically via `?stb_dynamic_css=1` for the preview iframe.
- **Fallback**: If DB options are empty, the plugin falls back to reading the original files in the plugin root.
- `STB_Stitch_Client` resolves auth server-side in this order: `STITCH_API_KEY` env, `STITCH_API_KEY` constant, then the saved `stb_stitch_api_key` option.
- The Bricks button only loads when `bricks_is_builder_main()` is true.
- Tailwind is no longer injected by the plugin.
- JS/CSS are served directly from `assets/`. There is no bundler, lint, typecheck, test runner, or CI config in this repo.

## Import Flow
- **Primary Flow (AI Designer)**:
  1. User provides a text prompt or reference image in `AI Designs > AI Designer`.
  2. AI generates Core Framework-compliant HTML/CSS using the DB-stored `DESIGN.md` and cheatsheet.
  3. User previews the result in a sandboxed iframe.
  4. User clicks "Copy to Bricks" and pastes into Bricks native `HTML & CSS to Bricks` importer.
- **Legacy Flow (Stitch)**:
  - The active builder flow is `builder-integration.js` -> `stb_fetch_screen_payload` -> `STB_Stitch_Client::import_screen()`.
  - The default handoff path copies sanitized `Body HTML` to the clipboard.
  - `Inspect Payload` opens the Phase 0 dialog so you can compare `Body HTML`, `Full HTML`, and `Inline CSS`.

## Verification Reality
- There is no trustworthy automated suite today. Root `test_*.php`, `dump_*.php`, and `parse_tags.php` are ad hoc helpers.
- Use `PHASE_0_VALIDATION.md` and `PHASE_0_RESULTS.md` to track the current Bricks native-import findings.
- The safest verification is manual:
  - **AI Designer**: Go to `AI Designs > AI Designer`, generate a design, and verify the preview matches the prompt.
  - **Bricks Import**: Copy the generated HTML and paste it into the Bricks builder to confirm native element creation.

## Fixtures
- `core-framework-css (do not delete)/core-framework.css` and `core-framework-css (do not delete)/core-framework-minified.css` are the **fallback** references for `DESIGN.md` and class/token usage if DB options are empty.
- `bricksmaven-coreframework-examples/` is still useful as component reference material, but not runtime code.
- `AI_DESIGNER_IMPLEMENTATION_PLAN.md`: Tracker for the AI Designer feature rollout.
