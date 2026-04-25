# Stitch To Bricks Refactor Implementation Plan

## Objective

Refactor the plugin around two platform changes:

- Bricks 2.3+ now supports native `HTML & CSS to Bricks`, so this repo no longer needs its custom HTML-to-Bricks converter, parser, Tailwind-class registry logic, or direct postmeta save pipeline.
- Google Stitch now supports `DESIGN.md`, so design guidance should move from Tailwind-oriented prompt rules into a persistent design-system file centered on Core Framework classes, variables, and component conventions.

The target product is no longer a translation layer. It becomes a much thinner Stitch-to-Bricks bridge that fetches Stitch screen payloads directly from WordPress PHP and hands them off to Bricks' native import workflow.

## Execution Tracker

Current implementation phase: `Phase 6`

Important note:

- To reduce risk, part of Phase 4 was implemented early as transport groundwork.
- From this point forward, the work should continue in phase order from Phase 0 onward.

### Current Snapshot

- [x] Added a server-side PHP Stitch MCP client in `includes/class-stb-stitch-client.php`
- [x] Switched Stitch AJAX reads to use the PHP client instead of the Node proxy
- [x] Updated WordPress bootstrap/include order for the PHP Stitch client
- [x] Added Phase 0 native-import testing UI in `assets/js/builder-integration.js`
- [x] Added `PHASE_0_VALIDATION.md`
- [x] Manual validation indicates `Body HTML` works better than full-document HTML as the default Bricks handoff payload
- [x] Replaced the default builder import flow with a Bricks-native handoff flow based on `Body HTML`
- [x] Removed converter-owned runtime import/save logic
- [x] Run live Bricks validation and record the winning native import payload shape
- [x] Remove Tailwind runtime integration
- [x] Remove the Node proxy and related config
- [x] Add a Core Framework-oriented `DESIGN.md`
- [x] Update core runtime/docs to reflect the import-assistant architecture

### Phase Checklist

#### Phase 0: Validation Spikes

- [x] Add raw Stitch screen payload endpoint for validation
- [x] Add builder-side tester for `Body HTML`, `Full HTML`, and `Inline CSS`
- [x] Add Phase 0 validation notes and test matrix
- [x] Establish `Body HTML` as the current best default handoff payload
- [x] Run live validation in Bricks and capture initial findings
- [x] Decide the final preferred handoff contract: `HTML only` default with optional `Inline CSS` fallback
- [x] Decide whether sanitization is required for scripts or external resources

#### Phase 1: Remove Converter-Owned Runtime Responsibilities

- [x] Remove `includes/class-stb-converter.php`
- [x] Remove `includes/class-stb-parser.php`
- [x] Remove `includes/class-stb-cli.php`
- [x] Stop loading converter/parser/CLI classes from bootstrap
- [x] Remove `stb_import_screen` converter output path
- [x] Remove `stb_save_to_page`
- [x] Remove all direct writes to `_bricks_page_content_2`
- [x] Remove all direct writes/merges to `bricks_global_classes`
- [x] Remove all direct writes to `_bricks_editor_mode`

#### Phase 2: Remove Tailwind Assumptions

- [x] Remove Tailwind CDN injection from `stitch-to-bricks.php`
- [x] Remove Tailwind-specific runtime comments, logs, and active UI/runtime copy
- [x] Remove Tailwind-era helper files that are no longer needed
- [x] Replace Tailwind-oriented docs with Core Framework-oriented guidance

#### Phase 3: Rebuild The Builder UI Around Bricks Native Import

- [x] Replace `Legacy Import` with the new native handoff flow after Phase 0 validation completes
- [x] Keep project and screen browsing, but remove converter-oriented messaging from the active flow
- [x] Implement the Bricks-native handoff UX based on current Phase 0 findings
- [x] Ensure the active builder UI no longer references `globalClasses`, converter output, or save-to-page

#### Phase 4: Move Stitch Transport Into PHP And Remove The Proxy

- [x] Add a PHP Stitch MCP client inside `includes/`
- [x] Port the Stitch JSON-RPC request/response logic into PHP
- [x] Update AJAX handlers to use the PHP client
- [x] Add server-side Stitch env support in Docker config
- [x] Remove `node-mcp-proxy/` runtime usage
- [x] Remove `STB_NODE_PROXY_URL` runtime plumbing
- [x] Verify supported Stitch flows work without the Node service

#### Phase 5: Adopt `DESIGN.md` As The Design Contract

- [x] Add root `DESIGN.md`
- [x] Encode Core Framework tokens, variables, utility families, and component rules
- [x] Keep only minimal prompt-level rules that `DESIGN.md` cannot reliably enforce
- [x] Add lightweight repo docs for the new Stitch guidance workflow

#### Phase 6: Cleanup And Rename For The New Product Shape

- [x] Delete stale converter-era support files
- [x] Audit remaining fixtures and keep only intentionally owned references
- [x] Update plugin description and active UI copy to match the thinner import-assistant architecture
- [x] Update `AGENTS.md` and related repo docs after the refactor lands

Intentional references still kept:

- `core-framework-css (do not delete)/`: authoritative Core Framework CSS for `DESIGN.md`
- `bricksmaven-coreframework-examples/`: component reference material
- `Default-CoreFrameworkProject.core`: retained as a project artifact/reference until the team decides otherwise
- `PHASE_0_VALIDATION.md` and `PHASE_0_RESULTS.md`: current native-import validation history

## Research Summary

### Verified external facts

- Bricks 2.3 introduced native `HTML & CSS to Bricks` import in the builder.
- Bricks documents this as a builder paste workflow, not as a public backend conversion API.
- Bricks can convert pasted HTML/CSS into native elements, global classes, and global variables.
- Bricks will not auto-execute pasted JavaScript or external stylesheets; those are moved into Code elements and require manual review/signing.
- `DESIGN.md` is a real Google/Stitch design-system format with YAML front matter for tokens and markdown body sections for rationale and guardrails.
- `DESIGN.md` is well-suited for persistent design tokens, typography, spacing, shape rules, and component-level guidance.
- Public Stitch docs do not clearly guarantee that `DESIGN.md` alone will force exact framework-specific class strings such as Core Framework utilities.

### Verified repo facts

- The current runtime is centered on `STB_Converter`, not on Bricks native import.
- The current plugin directly writes `_bricks_page_content_2`, merges into `bricks_global_classes`, and sets `_bricks_editor_mode`.
- The current import flow assumes the plugin owns conversion and saving.
- Tailwind is a hard runtime dependency today because `stitch-to-bricks.php` injects the Tailwind Play CDN globally.
- The saved WordPress API key setting is misleading because runtime auth currently comes from the Node proxy's `STITCH_API_KEY` env var, not from WordPress settings.
- The checked-in WP-CLI path is stale and broken.
- Most root helper scripts and JSON fixtures are ad hoc converter-era artifacts.

## Sources

- Bricks 2.3 release notes: `https://bricksbuilder.io/release/bricks-2-3/`
- Bricks `HTML & CSS to Bricks` docs: `https://academy.bricksbuilder.io/article/html-css-to-bricks/`
- Stitch `DESIGN.md` overview: `https://stitch.withgoogle.com/docs/design-md/overview`
- Google `DESIGN.md` repo README: `https://raw.githubusercontent.com/google-labs-code/design.md/main/README.md`
- Google `DESIGN.md` spec: `https://raw.githubusercontent.com/google-labs-code/design.md/main/docs/spec.md`

## Current State Assessment

### Current runtime path

1. `stitch-to-bricks.php` bootstraps the plugin, loads converter-related classes, and injects Tailwind Play CDN.
2. `includes/class-stb-builder-ui.php` loads `assets/js/builder-integration.js` in the Bricks main builder window.
3. `builder-integration.js` fetches Stitch projects/screens and calls `stb_import_screen`.
4. `includes/class-stb-ajax-handler.php` fetches raw HTML from the Node proxy.
5. `includes/class-stb-ajax-handler.php` instantiates `STB_Converter` and returns `elements`, `clipboard`, and `globalClasses`.
6. `builder-integration.js` calls `stb_save_to_page`.
7. `includes/class-stb-ajax-handler.php` writes Bricks page content and global classes directly into WordPress storage.

### Why this architecture is now wrong

- It duplicates a Bricks capability that now exists natively.
- It relies on undocumented internal knowledge of Bricks data storage.
- It keeps Tailwind as a runtime crutch even though the future design system is Core Framework-based.
- It treats Stitch output as something to normalize through custom heuristics instead of letting Bricks import the actual HTML/CSS.
- It keeps maintenance-heavy code that is now product debt, not product value.

## Recommended Product Direction

### Recommended target

Keep the plugin, but narrow its job:

- Browse Stitch projects and screens.
- Fetch the chosen screen payload from Stitch through a server-side PHP client.
- Prepare the payload for Bricks native import.
- Help the user hand the payload to Bricks in the builder.
- Keep Stitch auth on the server side only, not in browser-exposed code.

### What the plugin should stop doing

- Converting HTML into Bricks JSON.
- Generating or merging global classes itself.
- Writing `_bricks_page_content_2` directly.
- Setting `_bricks_editor_mode` directly as part of import.
- Injecting Tailwind Play CDN or assuming Tailwind utilities exist.
- Carrying converter-era prompt rules that optimize for Tailwind and custom class mapping.

### Important constraint

Bricks' documented flow is clipboard/paste-driven. Do not base the refactor on private or guessed Bricks internals. Unless a stable documented Bricks API is discovered during implementation, the plugin should use a user-visible handoff flow such as copy-to-clipboard plus import instructions, not a hidden save-to-post workaround.

## Target Architecture

### WordPress plugin

- `stitch-to-bricks.php` becomes a minimal bootstrap.
- `class-stb-builder-ui.php` remains, but only for a lightweight import assistant UI.
- `class-stb-ajax-handler.php` is reduced to raw Stitch browsing and raw payload retrieval.
- `class-stb-stitch-api.php` should be replaced or expanded into a real PHP Stitch MCP client.
- `class-stb-settings.php` is either removed or repurposed into diagnostics if there is still a real setting to expose.

### Stitch transport

- Preferred direction: remove the separate Node proxy and move Stitch MCP calls into WordPress PHP.
- The PHP client should call `https://stitch.googleapis.com/mcp` directly using `wp_remote_post()` with `X-Goog-Api-Key` on the server side.
- Keep the API key in env or config on the server. Do not localize it into browser JS.
- Only keep a separate proxy if PHP proves insufficient for Stitch response handling or hosting constraints.

### Stitch design-system layer

- Add a canonical repo `DESIGN.md` focused on Core Framework.
- Move design constraints out of Tailwind-oriented prompt guidance.
- Keep a thin Stitch prompt wrapper only for rules that `DESIGN.md` does not clearly guarantee, especially exact Core Framework class usage.

### Bricks handoff model

- Preferred baseline: fetch payload, copy the HTML/CSS payload for the user, and guide them to paste into Bricks' native importer.
- Optional enhancement: if a safe Bricks-side JS hook is verified during a spike, streamline the handoff inside the builder UI.
- Non-goal: custom PHP-side Bricks JSON generation.

## Preferred Stitch Transport: PHP Inside The Plugin

This should be treated as part of the main refactor, not as a separate product plan.

### Recommendation

- Do it in the same refactor plan.
- Treat it as the replacement for the current Node proxy layer, not as an independent initiative.
- The reason is architectural: once the converter is removed, the remaining backend job is just authenticated Stitch access plus payload normalization, which WordPress PHP can own directly.

### Recommended PHP client shape

Preferred file split:

- `includes/class-stb-stitch-client.php`: low-level Stitch MCP transport and response parsing.
- `includes/class-stb-stitch-api.php`: optional thin service layer for higher-level repo-specific operations, or delete it and keep a single client class if that stays small.
- `includes/class-stb-ajax-handler.php`: WordPress AJAX entrypoints only; no remote transport details.

Recommended methods:

- `STB_Stitch_Client::get_api_key(): string|WP_Error`
- `STB_Stitch_Client::get_mcp_url(): string`
- `STB_Stitch_Client::call_tool(string $tool_name, array $arguments = []): array|string|WP_Error`
- `STB_Stitch_Client::parse_mcp_response(array $response): array|string|WP_Error`
- `STB_Stitch_Client::parse_sse_body(string $raw_text): array|string`
- `STB_Stitch_Client::list_projects(): array|WP_Error`
- `STB_Stitch_Client::list_screens(string $project_id): array|WP_Error`
- `STB_Stitch_Client::get_screen(string $project_id, string $screen_id): array|WP_Error`
- `STB_Stitch_Client::download_screen_html(string $signed_url): string|WP_Error`
- `STB_Stitch_Client::import_screen(string $project_id, string $screen_id): array|WP_Error`

Expected behavior mapping from the current Node proxy:

- Node `callStitchTool()` -> PHP `call_tool()`.
- Node `/projects` -> PHP AJAX handler calling `list_projects()`.
- Node `/projects/:projectId/screens` -> PHP AJAX handler calling `list_screens()`.
- Node `/screens/:projectId/:screenId` -> PHP AJAX handler calling `get_screen()`.
- Node `/import-screen` -> PHP `import_screen()` that resolves the signed HTML URL and downloads the HTML.
- Node `/generate` -> only port if prompt-based generation survives the refactor.

Important implementation details:

- Send `Accept: application/json, text/event-stream` exactly as the Node proxy does today.
- Parse either JSON or SSE-like text bodies because Stitch may return either.
- Keep timeouts explicit and generous for slower Stitch responses.
- Normalize output shapes in PHP so browser JS only consumes clean `projects`, `screens`, and `html` payloads.
- Keep all Google credentials server-side.
- Use `WP_Error` consistently so AJAX handlers can produce clean user-facing errors.

## Critical Unknowns To Resolve First

These are the make-or-break validation items. They should be resolved before broad code deletion.

1. Whether Stitch can reliably provide the right import payload shape for Bricks.
2. Whether the correct payload should be full document HTML, fragment HTML, HTML plus CSS, or multiple paste steps.
3. Whether current Stitch screen exports include enough CSS information for Bricks to generate useful global classes and variables.
4. Whether Core Framework classes should already exist on the target site, or whether Bricks should generate new global classes from pasted CSS.
5. Whether `DESIGN.md` plus a very small prompt wrapper is sufficient to make Stitch emit Core Framework-compatible markup consistently.
6. Whether there is any stable Bricks-side automation hook worth using, or whether the plugin should explicitly remain a clipboard helper.
7. Whether Stitch responses that currently parse cleanly in Node also parse cleanly through WordPress HTTP handling in all expected environments.
8. Whether any hosting environment constraints make a separate proxy necessary despite PHP support being technically possible.

## Phase Plan

## Phase 0: Validation Spikes

Purpose: prove the new architecture before deleting the old one.

Deliverables:

- A short test matrix covering Bricks paste behavior for:
- full HTML document from Stitch
- fragment-only HTML
- HTML with inline `<style>`
- CSS-only paste
- markup using existing Core Framework classes without bundled CSS
- payloads containing Google Fonts, SVGs, or script tags
- A written conclusion on whether the plugin should ship HTML-only handoff, HTML-plus-CSS handoff, or a multi-step import assistant.
- A written conclusion on whether any Bricks-side JS integration is stable enough to use.
- A written conclusion on whether Stitch output must be sanitized before handoff.
- A written conclusion on whether the PHP Stitch client fully replaces the Node proxy for all supported flows.

Acceptance criteria:

- The team knows the exact payload format the plugin will hand to Bricks.
- The team knows whether manual paste is the stable product path.
- The team knows what `DESIGN.md` can control reliably and what still needs prompt-level reinforcement.
- The team knows whether the Node proxy can be deleted without losing any supported Stitch flow.

## Phase 1: Remove Converter-Owned Runtime Responsibilities

Purpose: stop the plugin from pretending it is a Bricks serializer.

Changes:

- Remove `includes/class-stb-converter.php`.
- Remove `includes/class-stb-parser.php`.
- Remove `includes/class-stb-cli.php`.
- Stop loading those classes from `stitch-to-bricks.php`.
- Remove `stb_import_screen` behavior that returns Bricks JSON.
- Remove `stb_save_to_page` and all direct writes to `_bricks_page_content_2`.
- Remove all code that merges `bricks_global_classes`.
- Remove all code that sets `_bricks_editor_mode` as part of import.
- Remove legacy fetch/generate paths that only existed to feed the converter.

Acceptance criteria:

- No runtime code path references `STB_Converter`, `STB_Parser`, `globalClasses`, `_bricks_page_content_2`, or `bricks_global_classes` for import.
- No AJAX endpoint returns Bricks JSON generated by this plugin.
- The plugin no longer writes Bricks internals directly.

## Phase 2: Remove Tailwind Assumptions

Purpose: fully detach the plugin from Tailwind-era output.

Changes:

- Remove `inject_tailwind_cdn()` and the `wp_head` hook from `stitch-to-bricks.php`.
- Remove any remaining Tailwind-specific messaging, comments, logs, or UI copy.
- Delete repo files that only exist because of Tailwind experiments or converter heuristics.
- Replace Tailwind-oriented docs with Core Framework-oriented design-system docs.

Acceptance criteria:

- The plugin does not inject Tailwind into WordPress frontend or builder pages.
- No active runtime file assumes Tailwind utility classes or Tailwind config.
- The remaining docs describe Core Framework and Bricks native import instead of Tailwind mapping.

## Phase 3: Rebuild The Builder UI Around Bricks Native Import

Purpose: keep the useful Stitch browsing UX without owning conversion.

Changes:

- Keep project and screen browsing in `builder-integration.js`, or rewrite it more minimally.
- Change the import action from `fetch -> convert -> save` into `fetch -> prepare -> handoff`.
- Decide the handoff UX based on Phase 0 findings.
- If manual paste is the baseline, add explicit in-builder guidance so the user understands the next step.
- If payload sanitization is needed, do it before the payload reaches the clipboard.
- If Bricks requires separate HTML and CSS paste steps, make that explicit in the UI.

Acceptance criteria:

- A user can browse Stitch screens from inside Bricks.
- A user can get the correct payload into Bricks native import without plugin-side JSON conversion.
- The builder UI no longer mentions converter output, globalClasses, or save-to-page.

## Phase 4: Move Stitch Transport Into PHP And Remove The Proxy

Purpose: collapse the extra Node runtime into the plugin so the product has one server-side backend.

Changes:

- Add a PHP Stitch MCP client inside `includes/`.
- Port the current Node transport behavior into PHP: JSON-RPC request building, header handling, JSON parsing, SSE parsing, and normalized payload mapping.
- Update `class-stb-ajax-handler.php` to call the PHP client directly instead of `proxy_get()` and `proxy_post()`.
- Remove `/generate` behavior entirely if prompt-based generation is no longer part of the product.
- Remove or repurpose `includes/class-stb-stitch-api.php` if its current shape no longer fits.
- Remove the misleading WordPress API key setting, or replace it with diagnostics that reflect reality.
- If settings remain, expose only real settings such as Stitch endpoint diagnostics or configuration state.
- Delete `node-mcp-proxy/` after the PHP client is verified.

Acceptance criteria:

- Stitch project browsing and screen import work without the Node service running.
- No active runtime path depends on `STB_NODE_PROXY_URL`.
- No UI or setting implies that WordPress stores the active Stitch API credential when it does not.
- There is no legacy generation path that bypasses the new design-system flow.

## Phase 5: Adopt `DESIGN.md` As The Design Contract

Purpose: replace fragile prompt engineering with a versioned design-system source of truth.

Changes:

- Add a root `DESIGN.md` that defines Core Framework-aligned tokens and guidance.
- Include YAML front matter for colors, typography, spacing, rounded values, and component tokens.
- Use markdown sections for Core Framework usage rules, component intent, accessibility rules, and prohibited output patterns.
- Encode Core Framework variable names and component semantics in the file.
- Keep a small complementary Stitch prompt for exact output rules that docs do not prove `DESIGN.md` can enforce by itself.
- Add lightweight repo docs explaining how the team uses `DESIGN.md` with Stitch.

Acceptance criteria:

- The repo has one canonical design-system file instead of scattered Tailwind prompt heuristics.
- The team can lint the file with `npx @google/design.md lint DESIGN.md`.
- Stitch guidance is centered on Core Framework tokens and variables, not Tailwind utilities.

## Phase 6: Cleanup And Rename For The New Product Shape

Purpose: remove dead files and rename the project around what it now does.

Recommended deletions after the runtime refactor lands:

- `includes/class-stb-converter.php`
- `includes/class-stb-parser.php`
- `includes/class-stb-cli.php`
- `node-mcp-proxy/`
- `test_converter.php`
- `test_tailwind.php`
- `test_header.php`
- `dump_classes.php`
- `dump_flex_classes.php`
- `parse_tags.php`
- `refactor.py`
- `tailwind.css`
- `tailwind2.css`
- `test_output.json`
- `test-output.json`
- `coreframework-maven`
- `stitch-payload-test.html` if it stays empty/unowned

Files to audit before deletion because they may still be useful as manual-import fixtures or design references:

- `payload-modern-boilerplate.html`
- `payload-modern-boilerplate-bricks`
- `payload-test2.html`
- `creative-agency-hero-desktop.html`
- `creative agency bricks`
- `bricksmaven-coreframework-examples/`
- `prompt_guide_stitch_to_bricks.md`

Rename/update candidates:

- Plugin description in `stitch-to-bricks.php`
- UI copy that says "translation layer" or implies one-click native conversion
- Repo docs including `AGENTS.md` once the refactor is merged

Acceptance criteria:

- The repo no longer contains stale converter-era tooling that confuses future work.
- The remaining fixtures are intentionally owned and documented.
- Product language matches the thinner bridge/import-assistant architecture.

## `DESIGN.md` Content Strategy For Core Framework

The new `DESIGN.md` should describe the design system in a way Stitch can persist across screens while staying aligned with Core Framework.

It should include:

- Brand overview and design intent.
- Color tokens mapped to the Core Framework variable model the team actually uses.
- Typography tokens that match the intended type scale.
- Spacing and radius scales that match Core Framework expectations.
- Component-level rules for buttons, cards, sections, nav, inputs, and badges.
- Do and don't rules such as accessibility, contrast, number of font weights, and consistency constraints.
- Explicit prose that tells Stitch to prefer existing Core Framework variables and classes over ad hoc CSS.

It should not assume:

- that `DESIGN.md` alone guarantees exact class-string output without validation.
- that Tailwind-like arbitrary utility generation remains part of the system.

Recommended companion prompt rules until validated otherwise:

- Use Core Framework classes and variables only.
- Prefer existing project variables over literal hex values when equivalents exist.
- Do not emit Tailwind CDN scripts, Tailwind config blocks, or Tailwind utility classes.
- Prefer fragment-ready HTML when the output is intended for Bricks import.

## Verification Plan

There is no trustworthy automated test suite today, so verification must be explicit and manual.

Primary verification path:

1. Run `docker compose up --build`.
2. Confirm WordPress loads at `http://localhost:8080`.
3. Confirm the Bricks setting `Bricks > Settings > Builder > HTML & CSS to Bricks` is enabled.
4. Open the Bricks main builder window, not the preview iframe.
5. Browse a Stitch project/screen through the plugin UI.
6. Execute the new handoff flow.
7. Paste into Bricks and verify native elements are created.
8. Verify any expected classes and variables are created or preserved.
9. Verify no plugin code writes `_bricks_page_content_2` or `bricks_global_classes` directly.
10. Verify no Tailwind script is injected into frontend output.

Manual test cases:

- screen with plain semantic HTML
- screen with Core Framework classes already present on the site
- screen with CSS variables in `:root`
- screen with external fonts
- screen with SVGs
- screen with script tags or external stylesheets so the Bricks Code-element path is exercised
- user without `Create global classes` permission to confirm degraded behavior is understandable

## Risks And Mitigations

### Risk: Bricks native import has no documented backend API

Mitigation:

- Treat manual or semi-manual builder handoff as the default supported path.
- Only automate further if a stable supported entry point is proven.

### Risk: Stitch export shape may not map cleanly to Bricks import

Mitigation:

- Run Phase 0 before deleting the old runtime.
- Decide on the exact payload contract early.

### Risk: `DESIGN.md` may not fully enforce Core Framework class strings

Mitigation:

- Keep a thin prompt wrapper focused on exact implementation rules.
- Validate output against a few representative screens before removing all prompt guidance.

### Risk: some root fixtures still have value

Mitigation:

- Audit before deletion.
- Keep only fixtures that are explicitly used for manual import validation.

### Risk: users expect one-click import because the current plugin saves directly

Mitigation:

- Update the product language and UI to describe the new Bricks-native import flow clearly.
- Make the handoff UX as short and obvious as possible.

## Definition Of Done

The refactor is complete when all of the following are true:

- The plugin no longer contains converter, parser, or direct Bricks-storage write logic.
- The plugin no longer injects Tailwind.
- The product uses Bricks native import as the official conversion path.
- The repo contains a canonical Core Framework-oriented `DESIGN.md` plan for Stitch.
- The remaining runtime code is clearly scoped to Stitch browsing, payload retrieval, and handoff.
- Legacy files and misleading settings have been removed or repurposed.
- Manual verification has been completed against the new builder flow.

## Recommended Execution Order

1. Phase 0 validation spike.
2. Build the new handoff UX in parallel with the old flow still present.
3. Remove converter-owned runtime code.
4. Remove Tailwind integration.
5. Simplify proxy and settings.
6. Add `DESIGN.md` and replace old design guidance docs.
7. Delete stale files and update product language/docs.

This order avoids deleting working behavior before the Bricks-native handoff path is proven.
