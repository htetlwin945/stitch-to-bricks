# AI Designer Implementation Plan

## Objective

Replace the external Stitch dependency with a self-contained AI design assistant powered by OpenAI's API. The plugin will allow users to generate Core Framework-compliant HTML/CSS designs via text prompts and image references, preview them in real-time, iterate with feedback, and save them as reusable WordPress custom posts for Bricks import.

## Execution Tracker

Current implementation phase: `Planning`

### Current Snapshot

- [x] Validate OpenAI GPT-4o Vision capabilities for HTML/CSS generation
- [x] Confirm Core Framework CSS fits within token limits
- [x] Design architecture for AI client, CPT storage, and sandboxed preview
- [ ] Implement core AI infrastructure and settings
- [ ] Build chat UI and live preview iframe
- [ ] Add image upload and iterative feedback loop
- [ ] Integrate with Bricks native import flow

### Phase Checklist

#### Phase 0: Core AI Infrastructure
- [ ] Add `class-stb-ai-client.php` with OpenAI API wrapper
- [ ] Implement `gpt-4o` and `gpt-4o-mini` model support
- [ ] Add Vision API support for image-to-design generation
- [ ] Create `core-framework-cheatsheet.md` for efficient context injection
- [ ] Add plugin settings page for API key, model selection, and token limits

#### Phase 1: Custom Post Type & Storage
- [ ] Register `stb_design` CPT with meta fields: `html_content`, `css_content`, `prompt_history`, `reference_image_url`
- [ ] Add AJAX endpoints for saving, loading, and listing designs
- [ ] Implement secure HTML/CSS sanitization before storage
- [ ] Add version history tracking for design iterations

#### Phase 2: AI Designer UI
- [ ] Build chat interface (`assets/js/ai-designer.js`) with prompt input
- [ ] Add image upload component (base64 or media library)
- [ ] Implement sandboxed `<iframe>` preview with Core Framework CSS injection
- [ ] Add "Generate" and "Refine" buttons with loading states
- [ ] Display token usage and cost estimation in real-time

#### Phase 3: Vision & Feedback Loop
- [ ] Handle image uploads and send to OpenAI Vision API
- [ ] Maintain conversation history in session/localStorage for iterative feedback
- [ ] Implement diff/preview comparison for refined designs
- [ ] Add "Accept", "Discard", and "Save to Library" actions

#### Phase 4: Bricks Integration & Polish
- [ ] Add "Import to Bricks" button that copies sanitized HTML to clipboard
- [ ] Add design library UI to browse saved `stb_design` posts
- [ ] Implement usage tracking and quota management
- [ ] Add error handling for API failures, rate limits, and invalid responses
- [ ] Finalize documentation and user guides

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                   WordPress Plugin (PHP)                    │
├─────────────────┬───────────────────┬───────────────────────┤
│  AI Client      │  Design Manager   │  Settings & Auth      │
│  - OpenAI API   │  - CPT: stb_design│  - API Key storage    │
│  - Vision supp. │  - HTML/CSS meta  │  - Model selection    │
│  - Token mgmt   │  - Version history│  - Usage tracking     │
└─────────────────┴───────────────────┴───────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│                   Admin/Builder UI (JS)                     │
├─────────────────┬───────────────────┬───────────────────────┤
│  Chat Interface │  Live Preview     │  Action Bar           │
│  - Prompt input │  - Iframe render  │  - Save to CPT        │
│  - Image upload │  - CF CSS inject  │  - Copy to Bricks     │
│  - Feedback loop│  - Isolated scope │  - Iterate/Refine     │
└─────────────────┴───────────────────┴───────────────────────┘
```

## Technical Challenges & Solutions

### Token Management (CSS Context)
- **Problem**: Sending full `core-framework.css` (~3k lines) wastes tokens and increases costs.
- **Solution**: 
  1. Create a `core-framework-cheatsheet.md` with only variables, component classes, and utility patterns (~500 lines).
  2. Send cheatsheet in system prompt; attach full CSS only when needed.
  3. Use `response_format: { type: "json_object" }` to ensure clean code extraction.

### Preview Isolation
- **Problem**: Generated HTML may conflict with WP admin styles.
- **Solution**: Render previews in a sandboxed `<iframe>` that injects only `core-framework.css` and generated HTML. Guarantees accurate rendering without admin CSS bleed.

### Security & Sanitization
- **Problem**: AI might output `<script>` tags, `on*` attributes, or malicious CSS.
- **Solution**: 
  1. Strip all `<script>`, `on*` attributes, and `@import` rules before saving/previewing.
  2. Use `wp_kses_post()` or custom sanitizer for HTML.
  3. Store API keys server-side only; never expose to browser JS.

### API Key & Authentication
- **Problem**: OpenAI uses API keys, not OAuth.
- **Solution**: Admin-level API key stored in `wp_options` with encryption. Optional per-user keys for multi-site setups. Implement usage tracking and quota limits.

## Phase Details

### Phase 0: Core AI Infrastructure
**Purpose**: Establish reliable OpenAI API communication with proper token management.

**Changes**:
- Create `includes/class-stb-ai-client.php` with methods:
  - `generate_design(string $prompt, array $images = []): array`
  - `refine_design(string $history, string $feedback, array $images = []): array`
  - `estimate_tokens(string $text): int`
- Add settings page under `Settings > Stitch to Bricks > AI Designer`
- Implement secure API key validation and test connection button
- Create `core-framework-cheatsheet.md` for efficient context injection

**Acceptance Criteria**:
- Plugin can successfully call OpenAI API with valid key
- System prompt correctly includes Core Framework rules
- Token estimation works for prompt and response
- Settings page saves and validates API key securely

### Phase 1: Custom Post Type & Storage
**Purpose**: Create persistent storage for generated designs with version tracking.

**Changes**:
- Register `stb_design` CPT with capabilities: `create_posts`, `edit_posts`, `delete_posts`
- Add meta fields: `html_content`, `css_content`, `prompt_history`, `reference_image_url`, `model_used`, `token_cost`
- Create AJAX endpoints: `stb_save_design`, `stb_load_design`, `stb_list_designs`
- Implement HTML/CSS sanitization pipeline before storage
- Add version history tracking (store iterations as post revisions or meta array)

**Acceptance Criteria**:
- Designs save to `stb_design` CPT with all metadata
- Sanitization strips scripts, external links, and dangerous attributes
- AJAX endpoints return clean JSON responses
- Version history allows rollback to previous iterations

### Phase 2: AI Designer UI
**Purpose**: Build intuitive chat interface with live preview and action controls.

**Changes**:
- Create `assets/js/ai-designer.js` with:
  - Chat input with prompt history
  - Image upload component (drag & drop + file picker)
  - Real-time token usage display
  - Loading states and error handling
- Implement sandboxed `<iframe>` preview:
  - Inject `core-framework.css` from plugin assets
  - Render generated HTML/CSS in isolation
  - Auto-resize iframe to fit content
- Add action bar: "Generate", "Refine", "Save", "Copy to Bricks", "Discard"
- Style UI to match WordPress admin design system

**Acceptance Criteria**:
- Users can type prompts and upload images
- Preview renders accurately with Core Framework styles
- Actions trigger correct AJAX calls with proper feedback
- UI is responsive and accessible in WP admin

### Phase 3: Vision & Feedback Loop
**Purpose**: Enable image-to-design generation and iterative refinement.

**Changes**:
- Handle image uploads:
  - Convert to base64 or upload to WP media library
  - Send to OpenAI Vision API with appropriate format
- Maintain conversation history:
  - Store in localStorage for session persistence
  - Include in API requests for context-aware refinement
- Implement diff/preview comparison:
  - Side-by-side view of previous vs current design
  - Highlight changes in HTML/CSS structure
- Add feedback prompts: "Make button larger", "Change primary color", "Add more spacing"

**Acceptance Criteria**:
- Image upload correctly processes and sends to Vision API
- AI generates accurate designs from reference images
- Feedback loop maintains context across iterations
- Diff view clearly shows design changes

### Phase 4: Bricks Integration & Polish
**Purpose**: Complete the workflow with Bricks import and production-ready features.

**Changes**:
- Add "Import to Bricks" button:
  - Copies sanitized HTML to clipboard
  - Shows step-by-step instructions for Bricks native import
- Build design library UI:
  - Browse saved `stb_design` posts in admin
  - Filter by date, model, token cost, or tags
  - Quick preview and import actions
- Implement usage tracking:
  - Log API calls, token usage, and costs per design
  - Display monthly usage summary in settings
- Add robust error handling:
  - API rate limits, invalid responses, network timeouts
  - User-friendly error messages with retry options
- Finalize documentation:
  - Setup guide for API key and settings
  - Best practices for prompts and image references
  - Troubleshooting guide for common issues

**Acceptance Criteria**:
- One-click import to Bricks works seamlessly
- Design library is searchable and filterable
- Usage tracking accurately reports costs and limits
- Error handling covers all edge cases gracefully
- Documentation is complete and user-friendly

## Verification Plan

### Manual Testing Path
1. Run `docker compose up --build`
2. Navigate to `Settings > Stitch to Bricks > AI Designer`
3. Enter valid OpenAI API key and save
4. Open AI Designer interface in WP admin
5. Test text prompt: "Create a hero section with primary button and badge"
6. Verify preview renders correctly with Core Framework classes
7. Test image upload: Upload reference screenshot
8. Verify AI generates design matching image layout
9. Test refinement: "Make the title larger and change button to secondary"
10. Verify iteration maintains context and updates preview
11. Save design and verify it appears in design library
12. Copy to clipboard and paste into Bricks native importer
13. Verify Bricks creates correct elements with proper classes

### Test Cases
- Simple text prompt with no image
- Complex prompt with multiple components
- Image-to-design generation from screenshot
- Iterative refinement with 3+ feedback loops
- API key validation and error handling
- Token limit exceeded scenario
- Invalid HTML/CSS response sanitization
- Bricks import with and without inline CSS
- Design library filtering and search
- Usage tracking accuracy

## Risks And Mitigations

### Risk: OpenAI API costs escalate with large CSS context
**Mitigation**:
- Use `core-framework-cheatsheet.md` instead of full CSS
- Implement token limits and cost warnings in settings
- Allow users to select cheaper `gpt-4o-mini` for testing

### Risk: AI generates non-compliant HTML/CSS
**Mitigation**:
- Strict system prompt with Core Framework rules
- Post-generation sanitization pipeline
- Validation step before preview rendering
- User feedback loop to correct mistakes

### Risk: Preview conflicts with WP admin styles
**Mitigation**:
- Sandboxed `<iframe>` with isolated CSS injection
- Only load `core-framework.css` in preview frame
- Reset styles inside iframe to prevent bleed

### Risk: API rate limits or downtime
**Mitigation**:
- Implement retry logic with exponential backoff
- Cache successful responses for similar prompts
- Display clear error messages with retry options
- Allow offline mode with saved designs

### Risk: Security vulnerabilities from AI-generated code
**Mitigation**:
- Strip all `<script>`, `on*`, `@import` before storage/preview
- Use `wp_kses_post()` and custom sanitizers
- Never execute AI-generated JS in WP context
- Store API keys server-side only

## Definition Of Done

The AI Designer is complete when all of the following are true:
- Plugin successfully communicates with OpenAI API using stored credentials
- Users can generate Core Framework-compliant designs via text prompts and image uploads
- Live preview renders accurately in sandboxed iframe with proper styling
- Iterative feedback loop maintains context and refines designs correctly
- Designs save to `stb_design` CPT with full metadata and version history
- HTML/CSS sanitization prevents security vulnerabilities
- One-click import to Bricks native workflow works seamlessly
- Design library allows browsing, filtering, and reusing saved designs
- Usage tracking accurately reports token costs and API limits
- Error handling covers all edge cases with user-friendly messages
- Documentation is complete and guides users through setup and usage

## Recommended Execution Order
1. Phase 0: Core AI Infrastructure
2. Phase 1: Custom Post Type & Storage
3. Phase 2: AI Designer UI
4. Phase 3: Vision & Feedback Loop
5. Phase 4: Bricks Integration & Polish

This order ensures foundational API and storage layers are stable before building the user-facing interface and advanced features.
