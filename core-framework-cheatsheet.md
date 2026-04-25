# Core Framework Cheatsheet

Use this reference when generating HTML/CSS for Bricks import. Always prefer these classes and variables over custom styling.

## CSS Variables

### Colors
```css
--primary: hsla(238, 100%, 62%, 1)
--primary-5 through --primary-90 (opacity ladders)
--primary-d-1 through --primary-d-4 (darker)
--primary-l-1 through --primary-l-4 (lighter)

--secondary: hsla(0, 94%, 68%, 1)
--secondary-5 through --secondary-90
--secondary-d-1 through --secondary-d-4
--secondary-l-1 through --secondary-l-4

--tertiary: hsla(198, 74%, 51%, 1)
--tertiary-5 through --tertiary-90
--tertiary-d-1 through --tertiary-d-4
--tertiary-l-1 through --tertiary-l-4

--bg-body: hsla(0, 0%, 90%, 1)
--bg-surface: hsla(0, 0%, 100%, 1)
--text-body: hsla(0, 0%, 25%, 1)
--text-title: hsla(0, 0%, 0%, 1)
--border-primary: hsla(0, 0%, 50%, 0.25)
--shadow-primary: hsla(0, 0%, 0%, 0.15)

--success: hsla(136, 95%, 56%, 1)
--error: hsla(351, 95%, 56%, 1)
--dark: hsla(0, 0%, 0%, 1)
--light: hsla(0, 0%, 100%, 1)
```

### Spacing
```css
--space-4xs: 0.49rem
--space-3xs: 0.66rem
--space-2xs: 0.82rem
--space-xs: 1.02rem
--space-s: 1.28rem
--space-m: 1.6rem
--space-l: 2rem
--space-xl: 2.5rem
--space-2xl: 3.13rem
--space-3xl: 3.91rem
--space-4xl: 4.88rem
```

### Typography
```css
--text-xs: 1.01rem
--text-s: 1.35rem
--text-m: 1.6rem
--text-l: 1.8rem
--text-xl: 2.02rem
--text-2xl: 2.28rem
--text-3xl: 2.56rem
--text-4xl: 2.88rem

--hero-title-size: var(--text-4xl)
--post-title-size: var(--text-2xl)
--nav-link-size: var(--text-s)
```

### Radius
```css
--radius-xs: 0.4rem
--radius-s: 0.6rem
--radius-m: 1rem
--radius-l: 1.6rem
--radius-xl: 2.6rem
--radius-full: 999rem
```

### Shadows
```css
--shadow-xs: 0 1px 2px var(--shadow-primary)
--shadow-s: 0 1.5px 3px var(--shadow-primary)
--shadow-m: 0 2px 6px var(--shadow-primary)
--shadow-l: 0 3px 12px var(--shadow-primary)
--shadow-xl: 0 6px 48px var(--shadow-primary)
```

### Layout
```css
--min-screen-width: 320px
--max-screen-width: 1400px
--columns-1 through --columns-8
```

## Component Classes

### Buttons
```css
.btn                    /* Base button */
.btn.primary            /* Primary color (default) */
.btn.secondary          /* Secondary color */
.btn.tertiary           /* Tertiary color */
.btn.ghost              /* Transparent background */
.btn.slight             /* Light background with border */
.btn.no-bg              /* No background, no border */
.btn.small              /* Smaller size */
.btn.large              /* Larger size */
```

### Badges
```css
.badge                  /* Base badge */
.badge.secondary        /* Secondary color text */
```

### Cards
```css
.card                   /* Base card with shadow */
.card.primary           /* Primary background */
.card.secondary         /* Secondary background */
```

### Links
```css
.link                   /* Base link with underline effect */
.link.secondary         /* Secondary color */
.link.tertiary          /* Tertiary color */
```

### Inputs & Selects
```css
.input                  /* Text input */
.select                 /* Dropdown select */
.checkbox               /* Checkbox */
.radio                  /* Radio button */
```

### Icons & Media
```css
.icon                   /* Base icon */
.icon.small             /* Small icon */
.icon.large             /* Large icon */
.icon.secondary         /* Secondary color */
.icon.tertiary          /* Tertiary color */
.icon.outline           /* Outlined circle background */
.icon.filled            /* Filled circle background */
.avatar                 /* Circular image */
.avatar.small           /* Small avatar */
.avatar.large           /* Large avatar */
```

### Dividers
```css
.divider                /* Horizontal line */
.divider.vertical       /* Vertical line */
```

## Utility Families

### Background Colors
```
.bg-primary, .bg-primary-10, .bg-primary-20, ... .bg-primary-90
.bg-primary-d-1 through .bg-primary-d-4
.bg-primary-l-1 through .bg-primary-l-4
.bg-secondary, .bg-secondary-10, ... .bg-secondary-90
.bg-secondary-d-1 through .bg-secondary-l-4
.bg-tertiary, .bg-tertiary-10, ... .bg-tertiary-90
.bg-tertiary-d-1 through .bg-tertiary-l-4
.bg-body, .bg-surface
.bg-dark, .bg-dark-10, ... .bg-dark-90
.bg-light, .bg-light-10, ... .bg-light-90
.bg-success, .bg-success-10, ... .bg-success-90
.bg-error, .bg-error-10, ... .bg-error-90
```

### Text Colors
```
.text-primary, .text-primary-10, ... .text-primary-90
.text-primary-d-1 through .text-primary-l-4
.text-secondary, .text-secondary-10, ... .text-secondary-90
.text-secondary-d-1 through .text-secondary-l-4
.text-tertiary, .text-tertiary-10, ... .text-tertiary-90
.text-tertiary-d-1 through .text-tertiary-l-4
.text-body, .text-title
.text-dark, .text-dark-10, ... .text-dark-90
.text-light, .text-light-10, ... .text-light-90
.text-success, .text-success-10, ... .text-success-90
.text-error, .text-error-10, ... .text-error-90
```

### Border Colors
```
.border-primary, .border-primary-10, ... .border-primary-90
.border-primary-d-1 through .border-primary-l-4
.border-secondary, .border-secondary-10, ... .border-secondary-90
.border-secondary-d-1 through .border-secondary-l-4
.border-tertiary, .border-tertiary-10, ... .border-tertiary-90
.border-tertiary-d-1 through .border-tertiary-l-4
.border-body, .border-surface
.border-dark, .border-dark-10, ... .border-dark-90
.border-light, .border-light-10, ... .border-light-90
```

### Spacing Utilities
```
.padding-4xs through .padding-4xl
.padding-left-4xs through .padding-left-4xl
.padding-right-4xs through .padding-right-4xl
.padding-top-4xs through .padding-top-4xl
.padding-bottom-4xs through .padding-bottom-4xl
.padding-horizontal-4xs through .padding-horizontal-4xl
.padding-vertical-4xs through .padding-vertical-4xl

.margin-4xs through .margin-4xl
.margin-left-4xs through .margin-left-4xl
.margin-right-4xs through .margin-right-4xl
.margin-top-4xs through .margin-top-4xl
.margin-bottom-4xs through .margin-bottom-4xl
.margin-horizontal-4xs through .margin-horizontal-4xl
.margin-vertical-4xs through .margin-vertical-4xl

.gap-4xs through .gap-4xl
```

### Layout Utilities
```
.flex                   /* display: flex */
.grid                   /* display: grid */
.justify-start          /* justify-content: flex-start */
.justify-center         /* justify-content: center */
.justify-end            /* justify-content: flex-end */
.justify-between        /* justify-content: space-between */
.justify-around         /* justify-content: space-around */
.align-start            /* align-items: flex-start */
.align-center           /* align-items: center */
.align-end              /* align-items: flex-end */
.align-stretch          /* align-items: stretch */
.flex-wrap              /* flex-wrap: wrap */
.flex-nowrap            /* flex-wrap: nowrap */
.flex-col               /* flex-direction: column */
.flex-row               /* flex-direction: row */
.text-center            /* text-align: center */
.text-left              /* text-align: left */
.text-right             /* text-align: right */
.w-full                 /* width: 100% */
.h-full                 /* height: 100% */
.max-w-full             /* max-width: 100% */
```

## Theme Wrappers
```
.cf-theme-dark          /* Dark mode at root level */
.cf-theme-light         /* Light mode at root level */
.theme-inverted         /* Invert theme inside parent */
.theme-always-light     /* Force light mode */
.theme-always-dark      /* Force dark mode */
```

## Generation Rules

1. **Output fragment-ready HTML**: Start at component root (e.g., `<section>`, `<div>`). No `<html>`, `<head>`, or `<body>`.
2. **Use Core Framework classes first**: `.btn`, `.card`, `.badge`, `.link`, `.input`, `.select`, `.icon`, `.avatar`, `.divider`.
3. **Use utility families for spacing/colors**: `.padding-*`, `.margin-*`, `.gap-*`, `.bg-*`, `.text-*`, `.border-*`.
4. **Use CSS variables for custom values**: `var(--primary)`, `var(--space-m)`, `var(--text-m)`, `var(--radius-m)`.
5. **No Tailwind**: Do not emit Tailwind classes, CDN scripts, or config.
6. **No external assets**: Do not use `<script>`, `<link rel="stylesheet">`, `@import`, or remote icon fonts.
7. **Inline SVG for icons**: Prefer inline SVG wrapped with `.icon` classes.
8. **Scoped CSS only**: If custom CSS is needed, use one small `<style>` block targeting the component root.
9. **Semantic HTML**: Use `section`, `header`, `main`, `nav`, `article`, `figure`, `ul`, `li`, `h1`-`h6`, `p`.
10. **Bricks-compatible**: Keep output compatible with Bricks native `HTML & CSS to Bricks` paste flow.
