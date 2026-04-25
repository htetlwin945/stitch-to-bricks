---
version: alpha
name: Stitch to Bricks Core Framework
description: Core Framework design contract for Stitch output that will be imported into Bricks via native HTML & CSS to Bricks.
colors:
  primary: "#3d40ff"
  primary-soft: "#d8d9ff"
  secondary: "#fb6060"
  tertiary: "#25a8de"
  surface: "#ffffff"
  canvas: "#e6e6e6"
  text-body: "#404040"
  text-title: "#000000"
  border: "#80808040"
  shadow: "#00000026"
  on-primary: "#ffffff"
  on-secondary: "#ffffff"
  on-surface: "#404040"
typography:
  hero-title:
    fontFamily: Inter, system-ui, sans-serif
    fontSize: 2.88rem
    fontWeight: 800
    lineHeight: 1.1
    letterSpacing: -0.02em
  title-lg:
    fontFamily: Inter, system-ui, sans-serif
    fontSize: 2.28rem
    fontWeight: 700
    lineHeight: 1.15
    letterSpacing: -0.01em
  body-md:
    fontFamily: Inter, system-ui, sans-serif
    fontSize: 1.6rem
    fontWeight: 400
    lineHeight: 1.6
  nav-link:
    fontFamily: Inter, system-ui, sans-serif
    fontSize: 1.35rem
    fontWeight: 600
    lineHeight: 1.2
  label-sm:
    fontFamily: Inter, system-ui, sans-serif
    fontSize: 1.35rem
    fontWeight: 500
    lineHeight: 1.2
rounded:
  xs: 0.4rem
  sm: 0.8rem
  md: 1.2rem
  lg: 2rem
  xl: 3.2rem
  full: 999rem
spacing:
  4xs: 0.49rem
  3xs: 0.66rem
  2xs: 0.82rem
  xs: 1.02rem
  sm: 1.28rem
  md: 1.6rem
  lg: 2rem
  xl: 2.5rem
  2xl: 3.13rem
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    typography: "{typography.body-md}"
    rounded: "{rounded.md}"
    padding: 1.28rem
  button-secondary:
    backgroundColor: "{colors.secondary}"
    textColor: "{colors.on-secondary}"
    typography: "{typography.body-md}"
    rounded: "{rounded.md}"
    padding: 1.28rem
  card-default:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.on-surface}"
    typography: "{typography.body-md}"
    rounded: "{rounded.md}"
    padding: 1.6rem
  badge-default:
    backgroundColor: "{colors.primary-soft}"
    textColor: "{colors.primary}"
    typography: "{typography.label-sm}"
    rounded: "{rounded.full}"
    padding: 0.82rem
  input-default:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text-title}"
    typography: "{typography.body-md}"
    rounded: "{rounded.md}"
    padding: 1.28rem
---

## Overview

This design system targets Stitch output that will be handed off to Bricks through native `HTML & CSS to Bricks`, not through a custom converter.

The visual language should feel modern, structured, and systemized rather than bespoke. Prefer the existing Core Framework vocabulary over one-off styling. Output should look like it belongs inside a reusable component framework: clear spacing rhythm, semantic color roles, restrained shadows, medium-soft radii, and strong hierarchy driven by type scale and surface contrast.

The most important principle is reuse. Stitch should prefer the Core Framework class and variable system already represented in `core-framework-css (do not delete)/core-framework.css` and `core-framework-css (do not delete)/core-framework-minified.css` instead of generating Tailwind utilities, inline design tokens, or ad hoc semantic classes.

Treat `core-framework-css (do not delete)/core-framework.css` as the authoritative source for token names, utility families, theme wrappers, and component class semantics. Treat `bricksmaven-coreframework-examples/` as supporting reference material, not the primary contract.

## Colors

Use the Core Framework variable model directly:

- Primary actions and links map to `var(--primary)`.
- Secondary emphasis maps to `var(--secondary)`.
- Tertiary accents map to `var(--tertiary)`.
- Default cards and panels use `var(--bg-surface)` on `var(--bg-body)`.
- Body text uses `var(--text-body)` and headings use `var(--text-title)`.
- Borders and subtle dividers use `var(--border-primary)`.

When additional shades are needed, prefer the existing laddered variables already present in Core Framework, such as `--primary-10`, `--primary-d-1`, `--secondary-d-1`, `--dark-10`, and `--light-10`, instead of inventing new hex values.

## Typography

Map typography to the Core Framework text scale instead of custom sizes.

- Hero titles should map to `var(--hero-title-size)` or `var(--text-4xl)`.
- Large section titles should map to `var(--post-title-size)` or `var(--text-2xl)`.
- Navigation and small UI labels should map to `var(--nav-link-size)` or `var(--text-s)`.
- Default body copy should map to `var(--text-m)` with comfortable line height.

Keep type modern and clean. Use high contrast for headings, avoid more than two font weights per screen, and keep letter-spacing modest except for deliberate micro-labels.

## Layout

Use Core Framework spacing variables and layout rhythm:

- Prefer `var(--space-xs)` through `var(--space-2xl)` for padding, gaps, and section spacing.
- Use the existing fluid spacing system instead of fixed pixel values whenever possible.
- Favor clean grid and stack layouts with generous vertical rhythm.
- Build fragment-ready sections that can be imported directly into Bricks without requiring document wrappers.

Structure should stay semantic and builder-friendly:

- Use `section`, `header`, `main`, `nav`, `article`, `figure`, `ul`, `li`, `h1` to `h6`, and `p` appropriately.
- Prefer simple nested wrappers over deeply custom DOM.
- Keep imported fragments self-contained and avoid hidden dependencies on head-level assets.
- If utility classes are needed, use the existing Core Framework utility families such as `.padding-*`, `.margin-*`, `.gap-*`, `.text-*`, `.bg-*`, `.text-primary-*`, `.text-secondary-*`, `.text-tertiary-*`, and `.border-*`.
- Prefer fragment-ready component roots that Bricks can paste directly. The safest default structure starts at the actual component root rather than a full document wrapper.

## Elevation & Depth

Depth should come from Core Framework shadow variables and surface contrast, not from dramatic effects.

- Cards use `var(--shadow-m)` by default.
- Inputs and subtle controls can use `var(--shadow-xs)` or `var(--shadow-s)`.
- Larger hero or spotlight surfaces can use `var(--shadow-l)` sparingly.

Use shadows to clarify hierarchy, not to create novelty.

## Shapes

Use the Core Framework radius system:

- Small utility elements can use `var(--radius-s)`.
- Buttons, inputs, badges, and cards should usually use `var(--radius-m)`.
- Large hero panels or special media wrappers may use `var(--radius-l)` or `var(--radius-xl)`.
- Pills and badges use `var(--radius-full)`.

Do not mix sharp and heavily rounded styles within the same screen unless there is a clear component-level reason.

## Components

Prefer the framework's existing component classes and variants instead of inventing new names:

- Buttons: use `.btn` with variants like `.secondary`, `.tertiary`, `.ghost`, `.slight`, `.small`, and `.large`.
- Cards: use `.card` with `.primary` or `.secondary` only when a surface truly needs stronger emphasis.
- Badges: use `.badge` with optional color-role variants.
- Links: use `.link` with semantic color variants instead of custom underline treatments.
- Inputs and selects: use `.input` and `.select`.
- Icons: use `.icon` with `small`, `large`, `secondary`, `tertiary`, `outline`, or `filled` modifiers.
- Media avatars: use `.avatar` sizing variants instead of handcrafted image wrappers.
- Dividers: use `.divider` and `.divider.vertical` for separators.
- Theme wrappers: respect `cf-theme-dark`, `cf-theme-light`, `theme-inverted`, `theme-always-light`, and `theme-always-dark` patterns instead of inventing parallel dark-mode systems.

For icons, prefer inline SVG wrapped or styled with Core Framework classes over remote icon fonts. Imported Bricks payloads should not depend on external icon font stylesheets.

When Stitch needs custom wrappers for layout, keep those wrappers class-light and let the main visual identity come from the framework component classes, utility families, and CSS variables.

## Do's and Don'ts

- Do output fragment-ready HTML that starts at the component root. No `<html>`, `<head>`, or `<body>` wrappers.
- Do prefer Core Framework CSS variables such as `var(--primary)`, `var(--space-m)`, `var(--text-m)`, and `var(--radius-m)`.
- Do use existing Core Framework classes like `.btn`, `.card`, `.badge`, `.link`, `.input`, `.select`, `.icon`, `.avatar`, and `.divider` whenever they fit.
- Do use existing Core Framework utility families such as `.padding-*`, `.margin-*`, `.gap-*`, `.bg-*`, `.text-*`, and `.border-*` before inventing custom wrapper classes.
- Do keep HTML semantic and Bricks-friendly.
- Do keep dark-mode-aware designs compatible with `cf-theme-dark`, `cf-theme-light`, and `theme-inverted` patterns when relevant.
- Do prefer sanitized body-fragment-ready output that Bricks can paste without document-level cleanup.
- Do keep optional custom CSS scoped to the component root when utility families are not enough.
- Don't emit Tailwind utility classes.
- Don't emit Tailwind CDN scripts, `tailwind.config`, or framework-specific setup code.
- Don't rely on external stylesheet links or script tags in the imported fragment.
- Don't rely on `@import` inside inline CSS.
- Don't rely on remote icon font stylesheets or other external asset bootstrapping for the core layout to render.
- Don't invent new semantic classes when an existing Core Framework class or CSS variable already expresses the same intent.
- Don't hardcode hex colors when an existing Core Framework variable covers the need.
