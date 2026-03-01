# Building UI Components for Stitch-to-Bricks Integration (v3.0)

## 1. Purpose

This document provides the definitive standard for creating production-ready UI components in Stitch (or any AI HTML generator) specifically optimized for seamless import into Bricks Builder via the `STB_Converter`. Adherence to these guidelines is mandatory to ensure components are perfectly mapped to native Bricks elements, retain full editability, and automatically inherit styling via Utility Classes.

## 2. Core Principles

### Principle 1: Utility-First CSS is Law (Tailwind CSS)
All components **MUST** be styled exclusively using Tailwind CSS utility classes. 
- **NO Custom CSS:** Do not write `<style>` blocks or custom CSS classes (like BEM) unless absolutely necessary for complex pseudo-selectors or keyframe animations that Tailwind cannot handle natively. Let the utility classes map directly to the Bricks global class registry.
- **Example:** Instead of `.card { display: flex; flex-direction: column; gap: 1rem; }`, you **must** use `<div class="flex flex-col gap-4">`.

### Principle 2: The Principle of Absolute Explicitness
Assume **nothing** about browser defaults or inherited styles. The visual fidelity in Bricks depends strictly on the utility classes you declare.

- **Rule 2.A: All Flexbox Containers MUST Be Fully Defined.**
  - If you use `flex`, you **MUST** also explicitly define its direction, alignment, and justification. Bricks Builder enforces its own defaults which will break your layout if you leave them ambiguous.
  - Correct: `class="flex flex-row items-center justify-between gap-4"`
  - Incorrect: `class="flex gap-4"` (Relies on implicit defaults).

- **Rule 2.B: No Style is Assumed, Especially Color & Typography.**
  - Every text element **MUST** declare its explicit size, weight, and color utility class (e.g., `text-lg font-bold text-slate-900`). Do **not** rely on inheritance from parent containers.

- **Rule 2.C: Use Standard HTML/Tailwind Colors or Hex:**
  - **Do NOT** invent custom semantic color classes like `bg-primary`, `text-secondary`, or `bg-background-dark`. The Bricks environment may not have these variables defined natively.
  - **Instead**, use explicit standard Tailwind color utilities (e.g., `bg-blue-600`, `text-slate-800`) or explicit arbitrary hex values (e.g., `bg-[#1a1a2e]`, `text-[#f8fafc]`).
  - **Avoid Tailwind opacity modifiers** on semantic colors (like `bg-primary/20`) as they may fail to resolve without a JIT compiler. Use arbitrary hex with alpha if opacity is needed (e.g., `bg-[#6366f133]`).

### Principle 3: Maximize Semantic HTML
Use HTML tags that describe the meaning of the content. The `STB_Converter` is recursively intelligent and will map these semantic tags into fully flexible native Bricks elements.

- Outermost component block: **`<section>`**, **`<main>`**, or **`<header>`** (These map beautifully to Bricks `div` containers with `customTags`).
- A group of controls or links: **`<nav>`**
- Isolated media layout: **`<figure>`**
- Flow text elements: Use **`<h1>`** to **`<h6>`**, **`<p>`**, or **`<span>`**.

**CRITICAL NOTE ON BUTTONS:**
If a button requires an icon or nested text layouts, **do not** use the native `<button>` tag, as Bricks heavily restricts nested elements inside its native button element. Instead, use an anchor tag (`<a>`) or a `<div>` styled to look like a button with `cursor-pointer`.

### Principle 4: Bricks Labeling Engine Optimization
The `STB_Converter` reads the component's *first* utility class and uses it to automatically label the element in the Bricks Structure Panel.
- **Rule:** Try to order your Tailwind classes so the most descriptive layout class comes first.
- Example: `<div class="flex items-center gap-2">` will be labeled `.flex` in Bricks, immediately telling the developer it's a wrapper container.

## 3. HTML Structure Standard

The component **MUST** follow this hierarchy and utilize purely Tailwind utilities.

```html
<!-- BLOCK: The entire component starts with a semantic <section> tag -->
<section class="flex flex-col w-full min-h-screen bg-slate-50 py-16 px-6 md:px-20">

    <!-- A layout wrapper for max-width (replaces .container) -->
    <div class="flex flex-col w-full max-w-[1200px] mx-auto gap-8">

        <!-- ELEMENT: Left-side content area -->
        <div class="flex flex-col gap-4">

            <!-- ELEMENT: Explicitly styled heading -->
            <h2 class="text-3xl font-extrabold text-slate-900 leading-tight">
                Customer Stories
            </h2>
            
            <p class="text-lg text-slate-600 leading-relaxed max-w-[600px]">
                See how teams use our platform to ship faster.
            </p>

            <!-- ELEMENT: A complex button built with a div/anchor for Bricks flexibility -->
            <a href="#" class="flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors w-fit">
                Start Building
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
        </div>

        <!-- ELEMENT: A gallery grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Card -->
            <div class="flex flex-col bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
                <!-- Content -->
            </div>
        </div>

    </div>
</section>
```

## 4. Asset & Content Requirements

### Image Sourcing
- Images **MUST** be sourced from high-quality placeholders. For user avatars, dashboard previews, or backgrounds, utilize static dummy image URLs (e.g., Unsplash structural URLs if provided contextually).
- Avoid data URIs (Base64) for large images, as they dramatically bloat the Bricks JSON payload.

### Iconography
- Always use Google Material Symbols.
- Implementation: `<span class="material-symbols-outlined">icon_name</span>`. The converter automatically detects these and maps them securely.

## 5. JavaScript Implementation Standard

### Principle 5.A: Absolute Minimal JS
Because the final destination is a Page Builder (Bricks), try to avoid custom DOM manipulation scripts. Bricks natively handles mobile menus, popups, and interactions.
- If JS is required for a preview in Stitch, scope it meticulously so it degrades gracefully when imported into Bricks.

## 6. Code Delivery & Scoping Requirements

### Principle 6.A: Strict HTML Scoping
The provided HTML **MUST** be a self-contained component fragment ready to be converted.
- The top-level element **MUST** always be the component wrapper (e.g., `<section>`).
- You **MUST NOT** include the following tags, as they wrap the entire Stitch preview and confuse the Bricks root importer:
    - `<html>`
    - `<head>`
    - `<body>`

```html
<!-- WRONG: Includes forbidden parent tags -->
<body>
    <section class="flex flex-col...">...</section>
</body>

<!-- CORRECT: Starts directly with the component's tag -->
<section class="flex flex-col...">
  <!-- Component content goes here -->
</section>
```

### Principle 6.B: No Extraneous `<style>` Tags
Because Bricks will automatically generate its own global stylesheet based on the mapped utility classes, you **MUST NOT** include a `<style>` block unless dealing with an overly complex `keyframes` animation or advanced `nth-child` grid layout that Tailwind arbitrary values cannot succinctly solve.
