# Stitch Prompt Rules

Use this alongside `DESIGN.md` when prompting Stitch. Keep the prompt small and let `DESIGN.md` carry the design system.

## Purpose

`DESIGN.md` is the main design contract.

This file only covers the implementation rules that still need explicit prompt reinforcement for this repo:

- exact Core Framework class usage
- fragment-ready Bricks import output
- no external asset bootstrapping
- minimal scoped CSS when utility classes are insufficient

## Minimal Rules

- Use `DESIGN.md` as the primary design system source of truth.
- Use Core Framework classes and variables from `core-framework-css (do not delete)/core-framework.css`.
- Prefer existing Core Framework component classes first: `.btn`, `.card`, `.badge`, `.link`, `.input`, `.select`, `.icon`, `.avatar`, `.divider`.
- If component classes are not enough, use Core Framework utility families only: `.bg-*`, `.text-*`, `.border-*`, `.padding-*`, `.margin-*`, `.gap-*`, and the existing text scale helpers.
- Output only the component fragment starting at the actual component root. Do not include `<html>`, `<head>`, or `<body>`.
- Do not emit `<script>` tags, external `<link rel="stylesheet">` tags, Tailwind utilities, `tailwind.config`, or `@import` rules.
- Prefer inline SVG for icons. Do not rely on remote icon fonts.
- If extra CSS is required, include one small scoped `<style>` block only, targeting the component root. Avoid global selectors unless they are true design tokens or theme hooks.
- Keep the output compatible with Bricks native `HTML & CSS to Bricks` paste flow.

## Copy/Paste Prompt

```text
Use the repo's DESIGN.md as the primary design system source of truth.

Implementation requirements for this output:
- Use Core Framework classes and variables from `core-framework-css (do not delete)/core-framework.css`.
- Prefer existing component classes first: `.btn`, `.card`, `.badge`, `.link`, `.input`, `.select`, `.icon`, `.avatar`, `.divider`.
- If component classes are insufficient, use only existing Core Framework utility families such as `.bg-*`, `.text-*`, `.border-*`, `.padding-*`, `.margin-*`, `.gap-*`, and the built-in text scale helpers.
- Output only the component fragment starting at the real component root. Do not include `<html>`, `<head>`, or `<body>`.
- Do not emit Tailwind classes, Tailwind config, `<script>` tags, external stylesheet links, or `@import` rules.
- Prefer inline SVG for icons. Do not rely on remote icon fonts.
- If additional CSS is absolutely required, include a single small scoped `<style>` block targeting the component root.
- Keep the result compatible with Bricks native `HTML & CSS to Bricks` import.
```
