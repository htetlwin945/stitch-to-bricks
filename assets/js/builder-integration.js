/**
 * Stitch-to-Bricks: Builder Integration
 *
 * Injects the "⚡ Stitch AI" button into the Bricks Builder topbar.
 * Clicking it opens a slide-in panel to browse Stitch projects + screens.
 *
 * Runs ONLY in the Bricks Builder main window (not the canvas iframe).
 * The toolbar is Vue-rendered, so we use MutationObserver to detect it.
 */

console.log('[STB] Builder integration loaded.');

// ─── Panel State ──────────────────────────────────────────────────────────────
const STB = {
    panelOpen: false,
    currentProject: null,
    nativeImportTest: null,
};

// ─── Inject Toolbar Button ────────────────────────────────────────────────────
function injectSTBButton() {
    if (document.getElementById('stb-fetch-btn')) return;

    const toolbar = document.querySelector('#bricks-toolbar');
    if (!toolbar) return;

    console.log('[STB] Injecting button into #bricks-toolbar.');

    const btn = document.createElement('button');
    btn.id = 'stb-fetch-btn';
    btn.title = 'Browse Stitch AI Designs';
    btn.setAttribute('style', [
        'display:inline-flex', 'align-items:center', 'gap:5px',
        'background:linear-gradient(135deg,#6366f1,#8b5cf6)',
        'color:#fff', 'padding:5px 12px', 'border-radius:6px',
        'border:none', 'cursor:pointer', 'font-size:12px', 'font-weight:600',
        'line-height:1.4', 'white-space:nowrap', 'flex-shrink:0',
        'z-index:9999', 'letter-spacing:0.3px',
        'box-shadow:0 2px 8px rgba(99,102,241,0.4)',
        'transition:opacity .15s ease',
    ].join(';'));
    btn.innerHTML = '⚡ Stitch AI';
    btn.addEventListener('click', togglePanel);
    btn.addEventListener('mouseover', () => btn.style.opacity = '0.85');
    btn.addEventListener('mouseout', () => btn.style.opacity = '1');

    const right = toolbar.querySelector('.bricks-toolbar-right');
    right ? right.prepend(btn) : toolbar.appendChild(btn);
}

const observer = new MutationObserver(() => {
    if (document.querySelector('#bricks-toolbar')) injectSTBButton();
});
observer.observe(document.documentElement, { childList: true, subtree: true });
injectSTBButton();

// ─── Panel HTML + CSS ─────────────────────────────────────────────────────────
function createPanel() {
    if (document.getElementById('stb-panel')) return;

    // Inject styles
    const style = document.createElement('style');
    style.id = 'stb-panel-styles';
    style.textContent = `
        #stb-panel {
            position: fixed; top: 0; right: -420px; width: 420px; height: 100vh;
            background: #1a1a2e; color: #e2e8f0; z-index: 999999;
            display: flex; flex-direction: column;
            box-shadow: -4px 0 40px rgba(0,0,0,0.5);
            transition: right 0.3s cubic-bezier(0.4,0,0.2,1);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        #stb-panel.open { right: 0; }

        #stb-panel-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 20px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            flex-shrink: 0;
        }
        #stb-panel-header h2 {
            margin: 0; font-size: 15px; font-weight: 700; color: #fff;
            letter-spacing: 0.3px;
        }
        #stb-panel-close {
            background: rgba(255,255,255,0.2); border: none; color: #fff;
            width: 28px; height: 28px; border-radius: 50%; cursor: pointer;
            font-size: 16px; line-height: 1; display: flex; align-items: center; justify-content: center;
        }
        #stb-panel-close:hover { background: rgba(255,255,255,0.35); }

        #stb-breadcrumb {
            padding: 10px 20px; font-size: 12px; color: #94a3b8;
            background: #16213e; display: flex; align-items: center; gap: 6px;
            flex-shrink: 0; min-height: 36px;
        }
        #stb-breadcrumb .stb-back-btn {
            background: none; border: none; color: #6366f1; cursor: pointer;
            font-size: 12px; padding: 0; display: flex; align-items: center; gap: 4px;
        }
        #stb-breadcrumb .stb-back-btn:hover { color: #818cf8; }

        #stb-panel-body {
            flex: 1; overflow-y: auto; padding: 16px;
        }
        #stb-panel-body::-webkit-scrollbar { width: 6px; }
        #stb-panel-body::-webkit-scrollbar-track { background: #16213e; }
        #stb-panel-body::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }

        .stb-loading {
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; height: 200px; gap: 12px; color: #64748b;
        }
        .stb-spinner {
            width: 32px; height: 32px; border: 3px solid #1e293b;
            border-top-color: #6366f1; border-radius: 50%;
            animation: stb-spin 0.7s linear infinite;
        }
        @keyframes stb-spin { to { transform: rotate(360deg); } }

        .stb-error {
            background: #2d1b1b; border: 1px solid #7f1d1d; border-radius: 8px;
            padding: 14px; color: #fca5a5; font-size: 13px; margin: 8px 0;
        }

        /* Project cards */
        .stb-projects-grid { display: flex; flex-direction: column; gap: 10px; }
        .stb-project-card {
            background: #16213e; border-radius: 10px; overflow: hidden;
            cursor: pointer; transition: transform .15s, box-shadow .15s;
            border: 1px solid #1e293b; display: flex; align-items: center; gap: 0;
        }
        .stb-project-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(99,102,241,0.25);
            border-color: #6366f1;
        }
        .stb-project-thumb {
            width: 80px; height: 60px; object-fit: cover; flex-shrink: 0;
            background: #0f172a;
        }
        .stb-project-thumb-placeholder {
            width: 80px; height: 60px; flex-shrink: 0;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
        }
        .stb-project-info { padding: 10px 14px; flex: 1; min-width: 0; }
        .stb-project-title {
            font-size: 13px; font-weight: 600; color: #e2e8f0;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .stb-project-meta {
            font-size: 11px; color: #64748b; margin-top: 3px;
        }
        .stb-project-arrow {
            padding: 0 14px; color: #475569; font-size: 16px; flex-shrink: 0;
        }

        /* Screen grid */
        .stb-screens-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
        }
        .stb-screen-card {
            background: #16213e; border-radius: 10px; overflow: hidden;
            cursor: pointer; transition: transform .15s, box-shadow .15s;
            border: 1px solid #1e293b;
        }
        .stb-screen-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(99,102,241,0.3);
            border-color: #6366f1;
        }
        .stb-screen-thumb-wrap {
            width: 100%; aspect-ratio: 9/6; overflow: hidden;
            background: #0f172a; position: relative;
        }
        .stb-screen-thumb {
            width: 100%; height: 100%; object-fit: cover;
        }
        .stb-screen-thumb-placeholder {
            width: 100%; height: 100%;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            display: flex; align-items: center; justify-content: center;
            font-size: 28px;
        }
        .stb-screen-info { padding: 8px 10px; }
        .stb-screen-title {
            font-size: 11px; font-weight: 600; color: #cbd5e1;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .stb-screen-meta { font-size: 10px; color: #475569; margin-top: 2px; }
        .stb-screen-import-btn {
            display: block; width: calc(100% - 20px); margin: 0 10px 10px;
            padding: 7px; background: linear-gradient(135deg,#6366f1,#8b5cf6);
            color: #fff; border: none; border-radius: 6px; cursor: pointer;
            font-size: 11px; font-weight: 600; text-align: center;
            transition: opacity .15s;
        }
        .stb-screen-import-btn:hover { opacity: 0.85; }
        .stb-screen-import-btn:disabled { opacity: 0.5; cursor: wait; }
        .stb-screen-actions {
            display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 0 10px 10px;
        }
        .stb-screen-actions .stb-screen-import-btn {
            width: 100%; margin: 0;
        }
        .stb-screen-native-btn {
            background: #0f172a; border: 1px solid #334155; color: #cbd5e1;
        }
        .stb-screen-native-btn:hover { border-color: #6366f1; color: #fff; }
        .stb-screen-primary-btn {
            background: linear-gradient(135deg,#6366f1,#8b5cf6);
        }

        #stb-native-import-modal {
            position: fixed; inset: 0; background: rgba(2,6,23,0.7); z-index: 1000000;
            display: flex; align-items: center; justify-content: center; padding: 24px;
            backdrop-filter: blur(10px);
        }
        #stb-native-import-modal[hidden] {
            display: none;
        }
        .stb-native-dialog {
            width: min(860px, 100%); max-height: min(90vh, 820px); overflow: hidden;
            background: #0f172a; border: 1px solid #334155; border-radius: 18px;
            box-shadow: 0 24px 80px rgba(0,0,0,0.45); display: flex; flex-direction: column;
        }
        .stb-native-header {
            display: flex; align-items: flex-start; justify-content: space-between; gap: 16px;
            padding: 20px 22px; border-bottom: 1px solid #1e293b;
        }
        .stb-native-header h3 {
            margin: 0; font-size: 16px; color: #f8fafc;
        }
        .stb-native-header p {
            margin: 6px 0 0; font-size: 12px; color: #94a3b8; line-height: 1.5;
        }
        .stb-native-close {
            border: none; background: #1e293b; color: #e2e8f0; width: 30px; height: 30px;
            border-radius: 999px; cursor: pointer; flex-shrink: 0;
        }
        .stb-native-close:hover { background: #334155; }
        .stb-native-body {
            padding: 20px 22px 22px; overflow: auto; display: grid; gap: 18px;
        }
        .stb-native-summary {
            display: flex; flex-wrap: wrap; gap: 8px;
        }
        .stb-native-badge {
            display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px;
            background: #111827; border: 1px solid #1f2937; border-radius: 999px;
            color: #cbd5e1; font-size: 11px; font-weight: 600;
        }
        .stb-native-badge strong {
            color: #fff;
        }
        .stb-native-grid {
            display: grid; gap: 18px; grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
        }
        .stb-native-panel {
            background: #111827; border: 1px solid #1f2937; border-radius: 14px; padding: 16px;
        }
        .stb-native-panel h4 {
            margin: 0 0 10px; font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; color: #818cf8;
        }
        .stb-native-variant-buttons {
            display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;
        }
        .stb-native-variant-buttons button,
        .stb-native-action-row button {
            border: 1px solid #334155; background: #0f172a; color: #cbd5e1; border-radius: 8px;
            padding: 8px 10px; cursor: pointer; font-size: 12px; font-weight: 600;
        }
        .stb-native-variant-buttons button.active {
            border-color: #6366f1; color: #fff; background: rgba(99,102,241,0.16);
        }
        .stb-native-action-row {
            display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px;
        }
        .stb-native-action-row .primary {
            border-color: #6366f1; background: linear-gradient(135deg,#6366f1,#8b5cf6); color: #fff;
        }
        .stb-native-preview {
            width: 100%; min-height: 260px; resize: vertical; border-radius: 10px; border: 1px solid #334155;
            background: #020617; color: #e2e8f0; padding: 12px; font-size: 12px; line-height: 1.5;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        .stb-native-preview-meta {
            margin-top: 8px; font-size: 11px; color: #64748b;
        }
        .stb-native-steps {
            margin: 0; padding-left: 18px; display: grid; gap: 8px; color: #cbd5e1; font-size: 13px; line-height: 1.5;
        }
        .stb-native-note {
            margin-top: 12px; font-size: 12px; color: #94a3b8; line-height: 1.5;
        }
        @media (max-width: 860px) {
            .stb-native-grid {
                grid-template-columns: 1fr;
            }
        }

        .stb-section-title {
            font-size: 11px; font-weight: 700; color: #6366f1; letter-spacing: 1px;
            text-transform: uppercase; margin-bottom: 12px;
        }

        #stb-panel-footer {
            padding: 12px 20px; background: #16213e; flex-shrink: 0;
            border-top: 1px solid #1e293b; font-size: 11px; color: #475569;
            text-align: center;
        }
    `;
    document.head.appendChild(style);

    // Build panel DOM
    const panel = document.createElement('div');
    panel.id = 'stb-panel';
    panel.innerHTML = `
        <div id="stb-panel-header">
            <h2>⚡ Stitch AI Designs</h2>
            <button id="stb-panel-close" title="Close">✕</button>
        </div>
        <div id="stb-breadcrumb">
            <span id="stb-breadcrumb-text">All Projects</span>
        </div>
        <div id="stb-panel-body">
            <div class="stb-loading">
                <div class="stb-spinner"></div>
                <span>Loading projects…</span>
            </div>
        </div>
        <div id="stb-panel-footer">Powered by Google Stitch AI</div>
    `;

    document.body.appendChild(panel);
    document.getElementById('stb-panel-close').addEventListener('click', closePanel);
}

// ─── Panel Toggle ─────────────────────────────────────────────────────────────
function togglePanel() {
    STB.panelOpen ? closePanel() : openPanel();
}

function openPanel() {
    STB.panelOpen = true;
    createPanel();
    setTimeout(() => document.getElementById('stb-panel')?.classList.add('open'), 10);
    loadProjects();
}

function closePanel() {
    STB.panelOpen = false;
    document.getElementById('stb-panel')?.classList.remove('open');
}

// ─── Breadcrumb ───────────────────────────────────────────────────────────────
function setBreadcrumb(text, showBack = false, backFn = null) {
    const el = document.getElementById('stb-breadcrumb');
    if (!el) return;
    el.innerHTML = showBack
        ? `<button class="stb-back-btn" id="stb-back">← Back</button>
           <span style="color:#475569">›</span>
           <span>${text}</span>`
        : `<span>${text}</span>`;
    if (showBack && backFn) {
        document.getElementById('stb-back')?.addEventListener('click', backFn);
    }
}

// ─── AJAX Helper ─────────────────────────────────────────────────────────────
async function stbAjax(action, extraData = {}) {
    const body = new URLSearchParams({
        action,
        nonce: stbData.nonce,
        post_id: stbData.postId,
        ...extraData,
    });
    const r = await fetch(stbData.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body,
    });
    const json = await r.json();
    if (!json.success) throw new Error(json.data || 'Unknown error');
    return json.data;
}

// ─── Load Projects ────────────────────────────────────────────────────────────
async function loadProjects() {
    const body = document.getElementById('stb-panel-body');
    if (!body) return;

    body.innerHTML = `<div class="stb-loading"><div class="stb-spinner"></div><span>Loading projects…</span></div>`;
    setBreadcrumb('All Projects');

    try {
        const data = await stbAjax('stb_list_projects');
        const projects = data?.projects || [];

        if (!projects.length) {
            body.innerHTML = `<div class="stb-error">No Stitch projects found. Create a project at <a href="https://stitch.withgoogle.com" target="_blank" style="color:#818cf8">stitch.withgoogle.com</a>.</div>`;
            return;
        }

        body.innerHTML = `
            <div class="stb-section-title">${projects.length} Projects</div>
            <div class="stb-projects-grid" id="stb-projects-grid"></div>
        `;

        const grid = document.getElementById('stb-projects-grid');
        projects.forEach(project => {
            const card = document.createElement('div');
            card.className = 'stb-project-card';
            const screenCount = project.screenCount || (project.screens?.length ?? 0);
            const device = project.deviceType === 'MOBILE' ? '📱' : '🖥️';

            const thumb = project.thumbnail
                ? `<img class="stb-project-thumb" src="${project.thumbnail}" alt="" loading="lazy">`
                : `<div class="stb-project-thumb-placeholder">🎨</div>`;

            card.innerHTML = `
                ${thumb}
                <div class="stb-project-info">
                    <div class="stb-project-title">${escHtml(project.title)}</div>
                    <div class="stb-project-meta">${device} ${project.deviceType || 'DESKTOP'} · ${screenCount} screens</div>
                </div>
                <div class="stb-project-arrow">›</div>
            `;
            card.addEventListener('click', () => loadScreens(project));
            grid.appendChild(card);
        });

    } catch (err) {
        console.error('[STB] loadProjects error:', err);
        body.innerHTML = `<div class="stb-error">Failed to load projects: ${escHtml(err.message)}</div>`;
    }
}

// ─── Load Screens for a Project ───────────────────────────────────────────────
async function loadScreens(project) {
    const body = document.getElementById('stb-panel-body');
    if (!body) return;

    STB.currentProject = project;
    body.innerHTML = `<div class="stb-loading"><div class="stb-spinner"></div><span>Loading screens…</span></div>`;
    setBreadcrumb(escHtml(project.title), true, loadProjects);

    try {
        // Try the list_screens endpoint first; fall back to screenInstances in project data
        let screens = [];
        try {
            const data = await stbAjax('stb_list_screens', { project_id: project.id });
            screens = data?.screens || [];
        } catch {
            // Fall back to screens embedded in project data from list_projects
            screens = (project.screens || []).map(s => ({
                id: s.id,
                title: s.label || `Screen ${s.id.substring(0, 8)}`,
                screenshot: null,
                width: s.width,
                height: s.height,
            }));
        }

        if (!screens.length) {
            body.innerHTML = `<div class="stb-error">No screens found in this project.</div>`;
            return;
        }

        const visibleScreens = screens.filter(s => !s.hidden);
        const displayScreens = visibleScreens.length > 0 ? visibleScreens : screens;

        body.innerHTML = `
            <div class="stb-section-title">${displayScreens.length} Screens</div>
            <div class="stb-screens-grid" id="stb-screens-grid"></div>
        `;

        const grid = document.getElementById('stb-screens-grid');
        displayScreens.forEach(screen => {
            const card = document.createElement('div');
            card.className = 'stb-screen-card';

            const thumb = screen.screenshot
                ? `<img class="stb-screen-thumb" src="${screen.screenshot}" alt="" loading="lazy">`
                : `<div class="stb-screen-thumb-placeholder">📄</div>`;

            const w = screen.width || '?';
            const h = screen.height || '?';

            card.innerHTML = `
                <div class="stb-screen-thumb-wrap">${thumb}</div>
                <div class="stb-screen-info">
                    <div class="stb-screen-title">${escHtml(screen.title || 'Untitled Screen')}</div>
                    <div class="stb-screen-meta">${w}×${h}</div>
                </div>
                <div class="stb-screen-actions">
                    <button class="stb-screen-import-btn stb-screen-native-btn" data-screen-action="native" data-screen-id="${screen.id}" data-project-id="${project.id}">
                        Inspect Payload
                    </button>
                    <button class="stb-screen-import-btn stb-screen-primary-btn" data-screen-action="import" data-screen-id="${screen.id}" data-project-id="${project.id}">
                        Import to Bricks
                    </button>
                </div>
            `;

            card.querySelector('[data-screen-action="import"]').addEventListener('click', (e) => {
                e.stopPropagation();
                startNativeImport(project.id, screen.id, screen.title, e.currentTarget);
            });

            card.querySelector('[data-screen-action="native"]').addEventListener('click', (e) => {
                e.stopPropagation();
                inspectNativeImport(project.id, screen.id, screen.title, e.currentTarget);
            });

            grid.appendChild(card);
        });

    } catch (err) {
        console.error('[STB] loadScreens error:', err);
        body.innerHTML = `<div class="stb-error">Failed to load screens: ${escHtml(err.message)}</div>`;
    }
}

// ─── Phase 0: Native Import Validation ───────────────────────────────────────
async function inspectNativeImport(projectId, screenId, screenTitle, btn) {
    const originalText = btn.textContent;
    btn.textContent = '⏳ Preparing…';
    btn.disabled = true;

    try {
        console.group(`[STB] Native import test: "${screenTitle}"`);
        console.log('[STB] Fetching raw Stitch payload for Bricks native import validation…');

        const payload = await stbAjax('stb_fetch_screen_payload', {
            project_id: projectId,
            screen_id: screenId,
        });

        const variants = buildNativeImportVariants(payload?.html || '');
        openNativeImportDialog(screenTitle, payload, variants, { mode: 'inspect' });

        console.log('[STB] Native import variants:', {
            fullHtmlLength: variants.fullHtml.length,
            bodyHtmlLength: variants.bodyHtml.length,
            inlineCssLength: variants.inlineCss.length,
            summary: variants.summary,
        });
        console.groupEnd();
    } catch (err) {
        console.error('[STB] Native import test failed:', err);
        console.groupEnd();
        showToast('Native import test failed: ' + err.message, 'error');
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
}

async function startNativeImport(projectId, screenId, screenTitle, btn) {
    const originalText = btn.textContent;
    btn.textContent = '⏳ Preparing…';
    btn.disabled = true;

    try {
        console.group(`[STB] Native import handoff: "${screenTitle}"`);
        console.log('[STB] Fetching raw Stitch payload for Bricks handoff…');

        const payload = await stbAjax('stb_fetch_screen_payload', {
            project_id: projectId,
            screen_id: screenId,
        });

        const variants = buildNativeImportVariants(payload?.html || '');
        openNativeImportDialog(screenTitle, payload, variants, {
            mode: 'handoff',
            selectedVariant: variants.bodyHtml ? 'bodyHtml' : 'fullHtml',
        });

        await copyNativeVariant(STB.nativeImportTest?.selectedVariant || 'bodyHtml');
        console.groupEnd();
    } catch (err) {
        console.error('[STB] Native import handoff failed:', err);
        console.groupEnd();
        showToast('Import to Bricks failed: ' + err.message, 'error');
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
}

function buildNativeImportVariants(html) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html || '', 'text/html');
    const sanitizedBody = sanitizeBodyFragment(doc.body);
    const rawInlineCss = Array.from(doc.querySelectorAll('style'))
        .map((style) => style.textContent?.trim() || '')
        .filter(Boolean)
        .join('\n\n');
    const sanitizedInlineCss = sanitizeInlineCss(rawInlineCss);
    const externalStylesheets = Array.from(doc.querySelectorAll('link[rel="stylesheet"]')).map((link) => link.href).filter(Boolean);
    const scripts = Array.from(doc.querySelectorAll('script[src], script:not([src])'));
    const rootVariableCount = (sanitizedInlineCss.css.match(/--[a-z0-9-_]+\s*:/gi) || []).length;

    return {
        fullHtml: html || '',
        bodyHtml: sanitizedBody.html,
        inlineCss: sanitizedInlineCss.css,
        summary: {
            hasDoctype: /^\s*<!doctype/i.test(html || ''),
            styleTagCount: doc.querySelectorAll('style').length,
            externalStylesheetCount: externalStylesheets.length,
            externalStylesheets,
            scriptCount: scripts.length,
            strippedBodyScriptCount: sanitizedBody.removedScripts,
            strippedBodyStyleCount: sanitizedBody.removedStyles,
            strippedBodyStylesheetCount: sanitizedBody.removedStylesheets,
            strippedBodyNoscriptCount: sanitizedBody.removedNoscript,
            strippedCssImportCount: sanitizedInlineCss.removedImports,
            rootVariableCount,
            bodyNodeCount: doc.body?.children?.length || 0,
        },
    };
}

function sanitizeBodyFragment(body) {
    if (!body) {
        return {
            html: '',
            removedScripts: 0,
            removedStyles: 0,
            removedStylesheets: 0,
            removedNoscript: 0,
        };
    }

    const clone = body.cloneNode(true);
    const removedScripts = clone.querySelectorAll('script').length;
    const removedStyles = clone.querySelectorAll('style').length;
    const removedStylesheets = clone.querySelectorAll('link[rel="stylesheet"]').length;
    const removedNoscript = clone.querySelectorAll('noscript').length;

    clone.querySelectorAll('script, style, link[rel="stylesheet"], noscript').forEach((node) => node.remove());

    return {
        html: clone.innerHTML.trim(),
        removedScripts,
        removedStyles,
        removedStylesheets,
        removedNoscript,
    };
}

function sanitizeInlineCss(css) {
    const imports = css.match(/^\s*@import[^;]+;?/gim) || [];
    return {
        css: css.replace(/^\s*@import[^;]+;?\s*/gim, '').trim(),
        removedImports: imports.length,
    };
}

function openNativeImportDialog(screenTitle, payload, variants, options = {}) {
    closeNativeImportTester();

    const mode = options.mode || 'inspect';
    const selectedVariant = options.selectedVariant || (variants.bodyHtml ? 'bodyHtml' : 'fullHtml');

    STB.nativeImportTest = {
        screenTitle,
        payload,
        variants,
        selectedVariant,
        mode,
    };

    const title = mode === 'handoff' ? 'Import to Bricks' : 'Phase 0 Native Import Validation';
    const subtitle = mode === 'handoff'
        ? `${escHtml(screenTitle || 'Untitled Screen')} · Sanitized Body HTML is preselected because it performed better in Phase 0 validation. Paste it into Bricks now, and use Inline CSS only if the imported result needs a second styling pass.`
        : `${escHtml(screenTitle || 'Untitled Screen')} · Use these variants to test Bricks' native HTML & CSS importer before we remove the legacy converter flow.`;
    const stepOne = mode === 'handoff'
        ? 'The preferred payload has been copied to your clipboard. It is a sanitized Body HTML fragment with scripts, style tags, stylesheet links, and noscript tags removed from the body content.'
        : 'Confirm <code>Bricks &gt; Settings &gt; Builder &gt; HTML &amp; CSS to Bricks</code> is enabled.';
    const stepTwo = mode === 'handoff'
        ? 'If the imported result is missing styles, copy <strong>Inline CSS</strong> next and paste that into Bricks as a second step. External stylesheet links are never auto-copied.'
        : 'Start with <strong>Body HTML</strong>. If Bricks misses structure, try <strong>Full HTML</strong>. Use <strong>Inline CSS</strong> only if Bricks needs a second CSS paste step.';
    const stepThree = mode === 'handoff'
        ? 'If Bricks creates Code elements for scripts or external resources, note that so we can finalize sanitization rules.'
        : 'Paste into the Bricks builder and record whether Bricks creates native elements, classes, and variables.';
    const stepFour = mode === 'handoff'
        ? 'If this screen needs a different payload than Body HTML, reopen the inspector and compare variants before we lock the final import contract.'
        : 'Note any Code elements created for scripts or external resources so we can decide how much sanitization the plugin should do.';

    const modal = document.createElement('div');
    modal.id = 'stb-native-import-modal';
    modal.innerHTML = `
        <div class="stb-native-dialog" role="dialog" aria-modal="true" aria-labelledby="stb-native-import-title">
            <div class="stb-native-header">
                <div>
                    <h3 id="stb-native-import-title">${title}</h3>
                    <p>${subtitle}</p>
                </div>
                <button class="stb-native-close" type="button" aria-label="Close">✕</button>
            </div>
            <div class="stb-native-body">
                <div class="stb-native-summary" id="stb-native-summary"></div>
                <div class="stb-native-grid">
                    <div class="stb-native-panel">
                        <h4>Payload Variants</h4>
                        <div class="stb-native-variant-buttons" id="stb-native-variant-buttons"></div>
                        <textarea class="stb-native-preview" id="stb-native-preview" readonly spellcheck="false"></textarea>
                        <div class="stb-native-preview-meta" id="stb-native-preview-meta"></div>
                        <div class="stb-native-action-row">
                            <button type="button" class="primary" id="stb-copy-selected">Copy Selected Variant</button>
                            <button type="button" id="stb-copy-full-html">Copy Full HTML</button>
                            <button type="button" id="stb-copy-inline-css">Copy Inline CSS</button>
                        </div>
                    </div>
                    <div class="stb-native-panel">
                        <h4>Manual Test Steps</h4>
                        <ol class="stb-native-steps">
                            <li>${stepOne}</li>
                            <li>${stepTwo}</li>
                            <li>${stepThree}</li>
                            <li>${stepFour}</li>
                        </ol>
                        <div class="stb-native-note" id="stb-native-note"></div>
                    </div>
                </div>
            </div>
        </div>
    `;

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeNativeImportTester();
        }
    });

    document.body.appendChild(modal);
    modal.querySelector('.stb-native-close')?.addEventListener('click', closeNativeImportTester);
    document.addEventListener('keydown', handleNativeImportEscape);

    renderNativeImportDialog();
}

function closeNativeImportTester() {
    const modal = document.getElementById('stb-native-import-modal');
    if (modal) modal.remove();
    document.removeEventListener('keydown', handleNativeImportEscape);
    STB.nativeImportTest = null;
}

function handleNativeImportEscape(event) {
    if (event.key === 'Escape') {
        closeNativeImportTester();
    }
}

function renderNativeImportDialog() {
    const state = STB.nativeImportTest;
    if (!state) return;

    const variants = [
        { key: 'bodyHtml', label: 'Body HTML', value: state.variants.bodyHtml },
        { key: 'fullHtml', label: 'Full HTML', value: state.variants.fullHtml },
        { key: 'inlineCss', label: 'Inline CSS', value: state.variants.inlineCss },
    ].filter((variant) => variant.value);

    if (!variants.some((variant) => variant.key === state.selectedVariant)) {
        state.selectedVariant = variants[0]?.key || 'fullHtml';
    }

    const buttons = document.getElementById('stb-native-variant-buttons');
    const preview = document.getElementById('stb-native-preview');
    const previewMeta = document.getElementById('stb-native-preview-meta');
    const summary = document.getElementById('stb-native-summary');
    const note = document.getElementById('stb-native-note');
    if (!buttons || !preview || !previewMeta || !summary || !note) return;

    buttons.innerHTML = variants.map((variant) => `
        <button type="button" data-variant-key="${variant.key}" class="${variant.key === state.selectedVariant ? 'active' : ''}">
            ${variant.label}
        </button>
    `).join('');

    buttons.querySelectorAll('[data-variant-key]').forEach((button) => {
        button.addEventListener('click', () => {
            state.selectedVariant = button.getAttribute('data-variant-key');
            renderNativeImportDialog();
        });
    });

    const selectedText = state.variants[state.selectedVariant] || '';
    const selectedVariantLabel = variants.find((variant) => variant.key === state.selectedVariant)?.label || state.selectedVariant;
    preview.value = selectedText;
    previewMeta.textContent = `${selectedText.length.toLocaleString()} chars`;

    summary.innerHTML = [
        badgeHtml('Variant', selectedVariantLabel),
        badgeHtml('Style tags', state.variants.summary.styleTagCount),
        badgeHtml('External CSS', state.variants.summary.externalStylesheetCount),
        badgeHtml('Scripts', state.variants.summary.scriptCount),
        badgeHtml('Body script strip', state.variants.summary.strippedBodyScriptCount),
        badgeHtml(':root vars', state.variants.summary.rootVariableCount),
        badgeHtml('Body nodes', state.variants.summary.bodyNodeCount),
    ].join('');

    note.innerHTML = [
        state.mode === 'handoff' ? 'Sanitized Body HTML is the default handoff payload based on manual validation.' : 'Use this dialog to compare payload variants before finalizing the handoff contract.',
        state.variants.summary.hasDoctype ? 'The Stitch export includes a full HTML document.' : 'The Stitch export does not include a full HTML document.',
        state.variants.summary.externalStylesheetCount > 0 ? `Bricks may convert external stylesheets into Code elements for review. Detected: ${state.variants.summary.externalStylesheetCount}.` : 'No external stylesheet links detected.',
        state.variants.summary.scriptCount > 0 ? `Detected ${state.variants.summary.scriptCount} script tag(s); the default body payload does not auto-copy them.` : 'No script tags detected in this payload.',
        state.variants.summary.strippedCssImportCount > 0 ? `Removed ${state.variants.summary.strippedCssImportCount} CSS @import rule(s) from the inline CSS fallback.` : 'No CSS @import rules were found in inline styles.',
    ].join(' ');

    const copySelected = document.getElementById('stb-copy-selected');
    const copyFullHtml = document.getElementById('stb-copy-full-html');
    const copyInlineCss = document.getElementById('stb-copy-inline-css');
    if (copySelected) copySelected.onclick = () => copyNativeVariant(state.selectedVariant);
    if (copyFullHtml) copyFullHtml.onclick = () => copyNativeVariant('fullHtml');
    if (copyInlineCss) copyInlineCss.onclick = () => copyNativeVariant('inlineCss');
}

function badgeHtml(label, value) {
    return `<span class="stb-native-badge">${escHtml(label)} <strong>${escHtml(String(value))}</strong></span>`;
}

async function copyNativeVariant(variantKey) {
    const state = STB.nativeImportTest;
    if (!state) return;

    const value = state.variants[variantKey] || '';
    if (!value) {
        showToast('No payload available for that variant.', 'warning');
        return;
    }

    try {
        await navigator.clipboard.writeText(value);
        const labels = {
            bodyHtml: 'Sanitized Body HTML',
            fullHtml: 'Full HTML',
            inlineCss: 'Inline CSS',
        };
        showToast(`${labels[variantKey] || variantKey} copied. Paste it into Bricks to validate native import.`);
    } catch (err) {
        console.error('[STB] Clipboard copy failed:', err);
        showToast('Clipboard copy failed. Select the text manually from the tester.', 'warning');
    }
}

// ─── Toast Notification ───────────────────────────────────────────────────────
function showToast(message, type = 'success') {
    const existing = document.getElementById('stb-toast');
    if (existing) existing.remove();

    const colors = { success: '#6366f1', error: '#ef4444', warning: '#f59e0b' };
    const toast = document.createElement('div');
    toast.id = 'stb-toast';
    toast.setAttribute('style', [
        'position:fixed', 'bottom:24px', 'left:50%',
        'transform:translateX(-50%)',
        `background:${colors[type] || colors.success}`,
        'color:#fff', 'padding:10px 20px', 'border-radius:8px',
        'font-size:13px', 'font-weight:500', 'z-index:9999999',
        'box-shadow:0 4px 20px rgba(0,0,0,0.4)',
        'max-width:360px', 'text-align:center',
        'animation:stb-fadein .2s ease',
    ].join(';'));
    toast.textContent = message;
    document.body.appendChild(toast);

    // Add fade-in keyframe if needed
    if (!document.getElementById('stb-toast-style')) {
        const s = document.createElement('style');
        s.id = 'stb-toast-style';
        s.textContent = `@keyframes stb-fadein{from{opacity:0;transform:translateX(-50%) translateY(8px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}`;
        document.head.appendChild(s);
    }

    setTimeout(() => toast.remove(), 4000);
}

// ─── Utility ──────────────────────────────────────────────────────────────────
function escHtml(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
