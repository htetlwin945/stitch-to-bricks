# Phase 0 Validation

## Goal

Validate how Bricks native `HTML & CSS to Bricks` behaves with real Stitch payloads so the final handoff contract can be locked down.

## New Builder Flow

Each Stitch screen card now exposes two actions:

- `Import to Bricks`: copies the preferred payload to the clipboard for Bricks native import.
- `Inspect Payload`: opens the validation dialog so you can compare payload variants.

The validation dialog provides copyable variants for:

- `Body HTML`
- `Full HTML`
- `Inline CSS`

## Test Matrix

Run these tests inside the Bricks main builder window with `Bricks > Settings > Builder > HTML & CSS to Bricks` enabled.

1. `Body HTML`
- Copy `Body HTML` from the Phase 0 dialog.
- Paste into Bricks.
- Record whether Bricks creates the expected native element structure.

2. `Full HTML`
- Copy `Full HTML`.
- Paste into Bricks.
- Record whether Bricks handles full documents better or worse than body-only payloads.

3. `Inline CSS`
- Copy `Inline CSS`.
- Paste into Bricks after an HTML test if Bricks appears to need a second CSS import step.
- Record whether classes and variables import more accurately after a separate CSS paste.

4. External Resources
- Use screens that contain Google Fonts, external stylesheets, SVGs, or scripts.
- Record whether Bricks creates Code elements and whether manual review/signing is required.

5. Core Framework Presence
- Test a screen on a site where Core Framework CSS/classes already exist.
- Record whether Bricks preserves useful structure without needing generated fallback styles.

## What To Record

- Best-performing payload variant: `Body HTML`, `Full HTML`, or multi-step `HTML + CSS`
- Whether Bricks creates native elements correctly
- Whether Bricks creates global classes correctly
- Whether Bricks creates global variables from `:root` CSS correctly
- Whether external stylesheets or scripts require sanitization before handoff
- Whether the plugin should default to a single copy action or a multi-step import assistant

## Current Decision Boundary

Current default: sanitized `Body HTML` is the handoff payload. Keep using `Inspect Payload` to validate whether any specific screen still needs `Full HTML` inspection or a second `Inline CSS` paste step.
