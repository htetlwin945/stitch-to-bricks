const express = require('express');
const cors = require('cors');

const app = express();
const port = process.env.PORT || 3000;
const STITCH_API_KEY = process.env.STITCH_API_KEY;

if (!STITCH_API_KEY) {
    console.error("FATAL: STITCH_API_KEY environment variable is required.");
    process.exit(1);
}

// Stitch MCP endpoint — confirmed from @_davideast/stitch-mcp source
const STITCH_MCP_URL = process.env.STITCH_HOST || 'https://stitch.googleapis.com/mcp';

app.use(cors());
app.use(express.json());

// ─── Helper: call a Stitch MCP tool ───────────────────────────────────────────
async function callStitchTool(toolName, toolArgs = {}) {
    const payload = {
        jsonrpc: '2.0',
        method: 'tools/call',
        params: { name: toolName, arguments: toolArgs },
        id: Date.now()
    };

    const response = await fetch(STITCH_MCP_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json, text/event-stream',
            'X-Goog-Api-Key': STITCH_API_KEY,
        },
        body: JSON.stringify(payload),
    });

    const rawText = await response.text();
    if (!response.ok) {
        throw new Error(`Stitch MCP error ${response.status}: ${rawText.substring(0, 300)}`);
    }

    // Handle SSE response
    if (response.headers.get('content-type')?.includes('text/event-stream')) {
        const chunks = [];
        for (const line of rawText.split('\n')) {
            if (line.startsWith('data: ')) {
                try {
                    const data = JSON.parse(line.slice(6));
                    if (data.result?.content) {
                        for (const item of data.result.content) {
                            if (item.type === 'text') chunks.push(item.text);
                        }
                    }
                } catch { }
            }
        }
        return chunks.join('');
    }

    // Handle JSON response — parse and extract text content
    try {
        const json = JSON.parse(rawText);
        const textContent = json?.result?.content?.find(c => c.type === 'text')?.text;
        return textContent ? JSON.parse(textContent) : json;
    } catch {
        return rawText;
    }
}

// ─── Health check ─────────────────────────────────────────────────────────────
app.get('/health', (req, res) => {
    res.json({ status: 'ok', endpoint: STITCH_MCP_URL, timestamp: new Date().toISOString() });
});

// ─── GET /projects — list all Stitch projects ─────────────────────────────────
app.get('/projects', async (req, res) => {
    try {
        console.log('[STB Proxy] Listing Stitch projects...');
        const data = await callStitchTool('list_projects', {});

        // Normalize: data may be { projects: [...] } or an array
        const projects = data?.projects || data || [];

        // Map to a clean shape for the frontend
        const cleaned = projects.map(p => ({
            id: p.name?.split('/').pop() || '',
            name: p.name || '',
            title: p.title || 'Untitled Project',
            thumbnail: p.thumbnailScreenshot?.downloadUrl || null,
            deviceType: p.deviceType || 'DESKTOP',
            screenCount: (p.screenInstances || []).length,
            screens: (p.screenInstances || []).map(s => ({
                id: s.sourceScreen?.split('/').pop() || s.id,
                sourceScreen: s.sourceScreen,
                label: s.label || null,
                width: s.width || 1280,
                height: s.height || 900,
                hidden: s.hidden || false,
            })),
            updatedAt: p.updateTime,
        }));

        res.json({ success: true, projects: cleaned });
    } catch (error) {
        console.error('[STB Proxy] list_projects error:', error);
        res.status(500).json({ error: error.message });
    }
});

// ─── GET /projects/:projectId/screens — get screens with screenshots ──────────
app.get('/projects/:projectId/screens', async (req, res) => {
    try {
        const { projectId } = req.params;
        console.log(`[STB Proxy] Getting screens for project ${projectId}...`);

        const data = await callStitchTool('list_screens', { project_id: projectId });
        const screens = data?.screens || data || [];

        const cleaned = screens.map(s => ({
            id: s.name?.split('/').pop() || '',
            name: s.name || '',
            title: s.title || 'Untitled Screen',
            screenshot: s.screenshot?.downloadUrl || null,
            width: s.width || '1280',
            height: s.height || '900',
            deviceType: s.deviceType || 'DESKTOP',
        }));

        res.json({ success: true, screens: cleaned });
    } catch (error) {
        console.error('[STB Proxy] list_screens error:', error);
        res.status(500).json({ error: error.message });
    }
});

// ─── GET /screens/:projectId/:screenId — get a single screen details ──────────
app.get('/screens/:projectId/:screenId', async (req, res) => {
    try {
        const { projectId, screenId } = req.params;
        console.log(`[STB Proxy] Getting screen ${screenId} from project ${projectId}...`);

        const data = await callStitchTool('get_screen', {
            project_id: projectId,
            screen_id: screenId,
        });

        const screen = data?.screen || data;
        res.json({
            success: true,
            screen: {
                id: screenId,
                title: screen?.title || 'Untitled Screen',
                screenshot: screen?.screenshot?.downloadUrl || null,
                htmlUrl: screen?.htmlCode?.downloadUrl || null,
                width: screen?.width || '1280',
                height: screen?.height || '900',
                deviceType: screen?.deviceType || 'DESKTOP',
            }
        });
    } catch (error) {
        console.error('[STB Proxy] get_screen error:', error);
        res.status(500).json({ error: error.message });
    }
});

// ─── POST /import-screen — fetch HTML for a screen and return it ──────────────
app.post('/import-screen', async (req, res) => {
    try {
        const { projectId, screenId } = req.body;
        if (!projectId || !screenId) {
            return res.status(400).json({ error: 'projectId and screenId are required' });
        }

        console.log(`[STB Proxy] Importing screen ${screenId} from project ${projectId}...`);

        // First get the screen to find the htmlCode download URL
        const screenData = await callStitchTool('get_screen', {
            project_id: projectId,
            screen_id: screenId,
        });

        const screen = screenData?.screen || screenData;
        const htmlDownloadUrl = screen?.htmlCode?.downloadUrl;

        if (!htmlDownloadUrl) {
            return res.status(404).json({ error: 'No HTML available for this screen' });
        }

        console.log(`[STB Proxy] Downloading HTML from signed URL...`);

        // Download the actual HTML from the signed URL
        const htmlResponse = await fetch(htmlDownloadUrl);
        if (!htmlResponse.ok) {
            return res.status(500).json({ error: `Failed to download HTML: ${htmlResponse.status}` });
        }

        const html = await htmlResponse.text();
        console.log(`[STB Proxy] HTML downloaded successfully (${html.length} chars)`);

        res.json({
            success: true,
            html,
            title: screen?.title || 'Imported Screen',
            width: screen?.width || '1280',
            height: screen?.height || '900',
        });

    } catch (error) {
        console.error('[STB Proxy] import-screen error:', error);
        res.status(500).json({ error: error.message });
    }
});

// ─── POST /generate — generate a new design from a prompt ────────────────────
app.post('/generate', async (req, res) => {
    try {
        const { prompt } = req.body;
        if (!prompt) {
            return res.status(400).json({ error: "Prompt is required." });
        }
        console.log(`[STB Proxy] Generating: "${prompt.substring(0, 80)}..."`);
        const html = await callStitchTool('generate_freeform_stream', { prompt });
        res.json({ success: true, html: html || '' });
    } catch (error) {
        console.error("[STB Proxy] generate error:", error);
        res.status(500).json({ error: error.message });
    }
});

app.listen(port, () => {
    console.log(`[STB Proxy] Running on port ${port}`);
    console.log(`[STB Proxy] Stitch MCP URL: ${STITCH_MCP_URL}`);
    console.log(`[STB Proxy] API Key: ${STITCH_API_KEY ? 'YES (' + STITCH_API_KEY.length + ' chars)' : 'MISSING'}`);
});
