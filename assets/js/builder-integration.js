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
                <button class="stb-screen-import-btn" data-screen-id="${screen.id}" data-project-id="${project.id}">
                    Import to Canvas
                </button>
            `;

            card.querySelector('.stb-screen-import-btn').addEventListener('click', (e) => {
                e.stopPropagation();
                importScreen(project.id, screen.id, screen.title, e.currentTarget);
            });

            grid.appendChild(card);
        });

    } catch (err) {
        console.error('[STB] loadScreens error:', err);
        body.innerHTML = `<div class="stb-error">Failed to load screens: ${escHtml(err.message)}</div>`;
    }
}

// ─── Import Screen into Bricks Canvas ────────────────────────────────────────
async function importScreen(projectId, screenId, screenTitle, btn) {
    const originalText = btn.textContent;

    const setStatus = (msg) => { btn.textContent = msg; };

    setStatus('⏳ Fetching…');
    btn.disabled = true;

    try {
        // ── Step 1: Fetch HTML + parse to Bricks elements ─────────────────
        console.group(`[STB] Import: "${screenTitle}"`);
        console.log('[STB] Step 1: Fetching screen from Stitch + parsing HTML…');
        console.log('[STB] project:', projectId, '| screen:', screenId);

        const importData = await stbAjax('stb_import_screen', {
            project_id: projectId,
            screen_id: screenId,
        });

        const elements = importData?.elements;
        const clipboard = importData?.clipboard;
        const globalClasses = importData?.globalClasses;
        const html = importData?.html;

        // ── Structured logging ────────────────────────────────────────────
        console.log('[STB] Raw HTML length:', html?.length ?? 0, 'chars');
        console.log('[STB] Converter produced', elements?.length ?? 0, 'elements,', globalClasses?.length ?? 0, 'global classes');

        if (elements?.length > 0) {
            console.log('[STB] Root elements:', elements.filter(e => e.parent === 0).map(e => `${e.id}(${e.name})`).join(', '));
            console.log('[STB] All elements:', elements.map(e => `${e.id}(${e.name})`).join(', '));
        } else {
            console.warn('[STB] ⚠ No elements from converter. Check PHP debug log.');
            console.log('[STB] Raw HTML (first 500 chars):', html?.substring(0, 500));
        }

        if (globalClasses?.length > 0) {
            console.log('[STB] Global classes:', globalClasses.map(c => `${c.name}(${c.id})`).join(', '));
        }

        if (!elements || elements.length === 0) {
            throw new Error('Converter returned 0 elements. Check STB_Converter PHP log.');
        }

        // ── Step 2: Save to Bricks post meta (content + globalClasses) ────
        setStatus('⏳ Saving…');
        console.log('[STB] Step 2: Saving', elements.length, 'elements + ', globalClasses?.length ?? 0, 'global classes to post', stbData.postId, '…');

        const saveData = await stbAjax('stb_save_to_page', {
            post_id: stbData.postId,
            elements: JSON.stringify(elements),
            globalClasses: JSON.stringify(globalClasses || []),
        });

        console.log('[STB] Save result:', saveData);
        console.groupEnd();

        // ── Step 3: Reload builder to see elements ────────────────────────
        setStatus('✅ Saved! Reloading…');
        showToast(`"${screenTitle}" imported! (${elements.length} elements) Reloading…`);

        // Short delay so user sees the success toast, then reload
        setTimeout(() => {
            window.location.reload();
        }, 1800);

    } catch (err) {
        console.error('[STB] ❌ Import failed:', err);
        console.groupEnd();

        setStatus('❌ Failed');
        showToast('Import failed: ' + err.message, 'error');

        setTimeout(() => {
            btn.textContent = originalText;
            btn.disabled = false;
        }, 3000);
    }
}

// ─── Inject Bricks Elements into Canvas ──────────────────────────────────────
function injectIntoBricks(elements, label) {
    try {
        // Bricks uses a global Vue store — try to access it
        if (window.bricksData || window.$bricks) {
            // Try the Bricks Builder JS API to add elements
            const bricksApp = document.querySelector('#bricks-builder')?.__vue_app__;
            if (bricksApp) {
                // Get the Bricks store
                const store = bricksApp.config.globalProperties.$store;
                if (store && store.dispatch) {
                    store.dispatch('builder/addElements', { elements, source: 'stitch' });
                    showToast(`"${label}" added to canvas!`);
                    return;
                }
            }
        }
        // Fallback: copy JSON to clipboard so user can paste
        navigator.clipboard.writeText(JSON.stringify(elements, null, 2)).then(() => {
            showToast(`"${label}" Bricks JSON copied to clipboard! Use Bricks > Import to paste.`);
        });
    } catch (err) {
        console.error('[STB] injectIntoBricks error:', err);
        showToast('Could not inject directly — JSON logged to console.', 'warning');
        console.log('[STB] Bricks elements JSON:', JSON.stringify(elements, null, 2));
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
