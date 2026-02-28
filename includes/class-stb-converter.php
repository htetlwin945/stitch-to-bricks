<?php
/**
 * STB_Converter — Converts Stitch HTML into native Bricks Builder + Core Framework elements.
 *
 * Architecture:
 *  1. Parse the Stitch HTML into a DOM
 *  2. Detect section type (hero, features, cta, header, footer, generic)
 *  3. Extract semantic content (headings, paragraphs, buttons, images, links)
 *  4. Build Bricks elements using Core Framework structure patterns
 *  5. Output the full Bricks clipboard format (content + globalClasses)
 *
 * Based on analysis of 5 Core Framework templates from bricksmaven.com.
 */

if (!defined('ABSPATH')) {
    exit;
}

class STB_Converter
{


    // ─── State ────────────────────────────────────────────────────────────────

    private array $content = [];  // flat array of Bricks elements
    private array $globalClasses = [];  // collected global classes
    private string $sectionSlug = '';  // e.g. "hero", "features", "cta"

    // ─── Public entry point ───────────────────────────────────────────────────

    /**
     * Convert Stitch HTML to a Bricks clipboard-format array.
     *
     * @param  string $html  Full Stitch HTML document
     * @param  string $title Screen title (used for section label and class naming)
     * @return array  { content, source, version, globalClasses, globalElements }
     */
    public function convert(string $html, string $title = '', array $meta = []): array
    {
        $this->content = [];
        $this->globalClasses = [];

        libxml_use_internal_errors(true);
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML(
            mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
            LIBXML_NOWARNING | LIBXML_NOERROR
        );
        libxml_clear_errors();

        $body = $doc->getElementsByTagName('body')->item(0);
        $root = $body ?: $doc->documentElement;

        // ── Detect section type, set slug ─────────────────────────────────────
        $this->sectionSlug = $this->detect_section_type($root, $title);
        $slug = $this->sectionSlug;

        stb_log("STB_Converter: Detected section type: {$slug} for \"{$title}\"");

        // ── Extract semantic content ───────────────────────────────────────────
        $content = $this->extract_content($root);

        stb_log('STB_Converter: Extracted — headings:' . count($content['headings'])
            . ' paragraphs:' . count($content['paragraphs'])
            . ' buttons:' . count($content['buttons'])
            . ' images:' . count($content['images'])
            . ' links:' . count($content['links']));

        // ── Build Bricks structure ─────────────────────────────────────────────
        // The generic walker parses the DOM and creates Bricks elements dynamically.
        $this->build_generic($content, $title, $root);


        return [
            'content' => array_values($this->content),
            'source' => 'bricksCopiedElements',
            'sourceUrl' => site_url(),
            'version' => '1.9.7.1',
            'globalClasses' => array_values($this->globalClasses),
            'globalElements' => [],
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // SECTION TYPE DETECTION
    // ═══════════════════════════════════════════════════════════════════════════

    private function detect_section_type(DOMNode $root, string $title): string
    {
        $html_lower = strtolower($root->ownerDocument
            ? $root->ownerDocument->saveHTML($root)
            : '');
        $title_lower = strtolower($title);

        // Title-based detection (most reliable)
        if (preg_match('/\b(nav|header|navigation|menu)\b/', $title_lower))
            return 'header';
        if (preg_match('/\b(footer)\b/', $title_lower))
            return 'footer';
        if (preg_match('/\b(hero|landing|homepage|variant)\b/', $title_lower))
            return 'hero';
        if (preg_match('/\b(feature|benefit|service|card|grid)\b/', $title_lower))
            return 'features';
        if (preg_match('/\b(cta|call.to.action|trial|signup|sign.up)\b/', $title_lower))
            return 'cta';

        // Structure-based detection
        $h1_count = $root->ownerDocument->getElementsByTagName('h1')->length;
        $h2_count = $root->ownerDocument->getElementsByTagName('h2')->length;
        $nav_count = $root->ownerDocument->getElementsByTagName('nav')->length;
        $img_count = $root->ownerDocument->getElementsByTagName('img')->length;
        $btn_count = count($this->find_tags($root, ['button', 'a']));
        $li_count = $root->ownerDocument->getElementsByTagName('li')->length;

        if ($nav_count > 0 && ($h1_count === 0 || strpos($html_lower, 'logo') !== false))
            return 'header';
        if (strpos($html_lower, 'copyright') !== false || strpos($html_lower, '©') !== false)
            return 'footer';
        if ($h1_count > 0)
            return 'hero';
        if ($li_count >= 6 && $img_count === 0)
            return 'footer';
        if ($img_count >= 2 && $h2_count > 0)
            return 'features';
        if ($btn_count >= 2 && $h2_count === 1)
            return 'cta';

        return 'generic';
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CONTENT EXTRACTION
    // ═══════════════════════════════════════════════════════════════════════════

    private function extract_content(DOMNode $root): array
    {
        $doc = $root->ownerDocument;

        // ── 1. Headings ─────────────────────────────────────────────────────────
        $headings = [];
        foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
            foreach ($doc->getElementsByTagName($tag) as $node) {
                $text = trim($node->textContent);
                if ($text !== '') {
                    $headings[] = [
                        'level' => (int) ltrim($tag, 'h'),
                        'text' => $text,
                        'node' => $node,   // keep reference for proximity search
                    ];
                }
            }
        }

        // ── 2. Find the "hero content ancestor" — the section-level container
        //       that holds the primary heading. We'll scope CTA/tagline search to it.
        $primaryHeadingNode = !empty($headings) ? $headings[0]['node'] : null;
        $heroAncestor = $primaryHeadingNode
            ? $this->find_content_ancestor($primaryHeadingNode)
            : $root;

        // ── 3. Tagline — short text ABOVE the primary heading in the hero scope ─
        $taglineText = $this->find_tagline($heroAncestor, $primaryHeadingNode);

        // ── 4. Paragraphs ───────────────────────────────────────────────────────
        $paragraphs = [];
        $scopeNode = $heroAncestor ?: $root;
        foreach ($scopeNode->childNodes as $n) {
            $this->collect_paragraphs($n, $paragraphs, 0, 3);
        }
        // Fallback: doc-wide paragraphs
        if (empty($paragraphs)) {
            foreach ($doc->getElementsByTagName('p') as $p) {
                $text = trim($p->textContent);
                if ($text !== '' && strlen($text) > 20) {
                    $paragraphs[] = $text;
                }
            }
        }

        // ── 5. CTA Buttons — ONLY from within the hero content ancestor ─────────
        //       This prevents nav "Log In / Sign Up" from leaking into hero CTAs.
        $buttons = $this->find_cta_buttons($heroAncestor ?: $root);

        // ── 6. Images ───────────────────────────────────────────────────────────
        $images = [];
        foreach ($doc->getElementsByTagName('img') as $img) {
            $src = $img->getAttribute('src') ?: $img->getAttribute('data-src') ?: '';
            $alt = $img->getAttribute('alt') ?: '';
            // Skip tiny icons (tracking pixels, 1x1, etc.)
            $w = (int) ($img->getAttribute('width') ?: 9999);
            $h = (int) ($img->getAttribute('height') ?: 9999);
            if ($src !== '' && $w > 20 && $h > 20 && !str_contains($src, 'data:image/gif')) {
                $images[] = ['url' => $src, 'alt' => $alt];
            }
        }

        // ── 7. Background from body/section inline CSS ──────────────────────────
        $background = $this->extract_background($doc);

        // ── 8. Nav links (for header/footer sections) ───────────────────────────
        $links = [];
        foreach ($doc->getElementsByTagName('a') as $a) {
            $text = trim($a->textContent);
            $href = $a->getAttribute('href') ?: '#';
            if ($text !== '' && strlen($text) < 40) {
                $links[] = ['text' => $text, 'url' => $href];
            }
        }

        // ── 9. Cards ────────────────────────────────────────────────────────────
        $cards = $this->extract_cards($root);

        // Strip node references — they can't be serialised
        foreach ($headings as &$h) {
            unset($h['node']);
        }

        stb_log('STB extract: tagline="' . $taglineText . '" buttons=' . count($buttons)
            . ' (' . implode(', ', array_column($buttons, 'text')) . ')'
            . ' images=' . count($images));

        return [
            'headings' => $headings,
            'paragraphs' => $paragraphs,
            'taglines' => $taglineText ? [$taglineText] : [],
            'buttons' => array_slice($buttons, 0, 4),
            'images' => $images,
            'links' => $links,
            'cards' => $cards,
            'background' => $background,
        ];
    }

    // ── Walk up the DOM to find the section-level content ancestor ────────────
    private function find_content_ancestor(?DOMNode $node): ?DOMNode
    {
        if (!$node)
            return null;
        $cur = $node->parentNode;
        $depth = 0;
        while ($cur && $cur->nodeType === XML_ELEMENT_NODE && $depth < 8) {
            $tag = strtolower($cur->nodeName);
            // Stop at semantic block containers
            if (in_array($tag, ['section', 'main', 'article', 'header'])) {
                return $cur;
            }
            // Also stop when the ancestor contains multiple headings — it's a page wrapper
            if ($tag === 'div' || $tag === 'body') {
                $h1s = $cur->ownerDocument->getElementsByTagName('h1');
                // If this div only spans the hero, use it
                if ($h1s->length <= 1) {
                    return $cur;
                }
            }
            $cur = $cur->parentNode;
            $depth++;
        }
        return $node->parentNode;
    }

    // ── Find the best tagline candidate near/above the primary heading ─────────
    private function find_tagline(?DOMNode $ancestor, ?DOMNode $headingNode): string
    {
        if (!$ancestor || !$headingNode)
            return '';

        $icon_classes = [
            'material-icons',
            'material-symbols',
            'material-symbols-outlined',
            'material-icons-outlined',
            'fa',
            'fas',
            'far',
            'fab',
            'bi'
        ];

        // Collect short text nodes that come BEFORE the heading inside the ancestor
        $candidates = [];
        $this->collect_nodes_before($ancestor, $headingNode, $candidates);

        foreach (array_reverse($candidates) as $node) {
            if (!($node instanceof DOMElement))
                continue;
            $tag = strtolower($node->nodeName);
            $cls = $node->getAttribute('class') ?: '';
            $text = trim($node->textContent);

            if ($text === '' || strlen($text) > 80 || strlen($text) < 3)
                continue;

            // Skip icon containers
            $is_icon = false;
            foreach ($icon_classes as $ic) {
                if (str_contains($cls, $ic)) {
                    $is_icon = true;
                    break;
                }
            }
            if ($is_icon)
                continue;

            // Skip snake_case technical strings
            if (preg_match('/^[a-z][a-z0-9_]{2,}$/', $text) && str_contains($text, '_'))
                continue;

            // Short span or p before the heading = tagline
            if (in_array($tag, ['span', 'p', 'div', 'small', 'em', 'strong'])) {
                return $text;
            }
        }
        return '';
    }

    // ── Collect all element nodes that appear before $target inside $parent ───
    private function collect_nodes_before(DOMNode $parent, DOMNode $target, array &$out): bool
    {
        foreach ($parent->childNodes as $child) {
            if ($child->isSameNode($target))
                return true;
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $out[] = $child;
                if ($this->collect_nodes_before($child, $target, $out))
                    return true;
            }
        }
        return false;
    }

    // ── Recursively collect paragraphs up to $maxDepth levels ─────────────────
    private function collect_paragraphs(DOMNode $node, array &$out, int $depth, int $maxDepth): void
    {
        if ($depth > $maxDepth)
            return;
        if ($node->nodeType !== XML_ELEMENT_NODE)
            return;
        if (strtolower($node->nodeName) === 'p') {
            $text = trim($node->textContent);
            if ($text !== '' && strlen($text) > 20) {
                $out[] = $text;
            }
            return;
        }
        foreach ($node->childNodes as $child) {
            $this->collect_paragraphs($child, $out, $depth + 1, $maxDepth);
        }
    }

    // ── Find CTA buttons scoped to a container ────────────────────────────────
    private function find_cta_buttons(DOMNode $scope): array
    {
        $buttons = [];
        $seen = [];

        // Priority 1: explicit <button> elements in scope
        if ($scope->ownerDocument) {
            $allBtns = $scope->ownerDocument->getElementsByTagName('button');
            foreach ($allBtns as $btn) {
                if (!$this->node_is_descendant($btn, $scope))
                    continue;
                $text = trim($btn->textContent);
                if ($text === '' || strlen($text) > 60 || isset($seen[$text]))
                    continue;
                $cls = $btn->getAttribute('class') ?: '';
                $type = (str_contains($cls, 'outline') || str_contains($cls, 'secondary') || str_contains($cls, 'ghost'))
                    ? 'outline' : 'primary';
                $seen[$text] = true;
                $buttons[] = ['text' => $text, 'type' => $type, 'url' => '#'];
            }
        }

        // Priority 2: <a> tags inside scope that look like CTAs (have btn/cta class or are near buttons)
        if ($scope->ownerDocument) {
            $allLinks = $scope->ownerDocument->getElementsByTagName('a');
            foreach ($allLinks as $a) {
                if (!$this->node_is_descendant($a, $scope))
                    continue;
                $text = trim($a->textContent);
                $href = $a->getAttribute('href') ?: '#';
                $cls = $a->getAttribute('class') ?: '';
                if ($text === '' || strlen($text) > 60 || isset($seen[$text]))
                    continue;
                // Skip pure nav links (no visual button styling, just plain hrefs)
                $looks_btn = str_contains($cls, 'btn')
                    || str_contains($cls, 'button')
                    || str_contains($cls, 'cta')
                    || str_contains($cls, 'primary')
                    || str_contains($cls, 'secondary');
                // Also include if parent is a div/container that wraps with other buttons
                $parent_cls = $a->parentNode instanceof DOMElement
                    ? ($a->parentNode->getAttribute('class') ?: '') : '';
                $in_cta_wrap = str_contains($parent_cls, 'btn') || str_contains($parent_cls, 'cta')
                    || str_contains($parent_cls, 'action') || str_contains($parent_cls, 'hero');
                if (!$looks_btn && !$in_cta_wrap && !empty($buttons)) {
                    // If we already have real buttons, only add styled anchors
                    continue;
                }
                $type = 'link';
                if ($looks_btn) {
                    $type = (str_contains($cls, 'outline') || str_contains($cls, 'secondary') || str_contains($cls, 'ghost'))
                        ? 'outline' : 'primary';
                }
                $seen[$text] = true;
                $buttons[] = ['text' => $text, 'type' => $type, 'url' => $href];
            }
        }

        return $buttons;
    }

    // ── Check if $node is a descendant of $ancestor ───────────────────────────
    private function node_is_descendant(DOMNode $node, DOMNode $ancestor): bool
    {
        $cur = $node->parentNode;
        while ($cur) {
            if ($cur->isSameNode($ancestor))
                return true;
            $cur = $cur->parentNode;
        }
        return false;
    }

    // ── Extract background gradient/color from page CSS ───────────────────────
    private function extract_background(DOMDocument $doc): array
    {
        $background = [];
        // Check body inline style
        $body = $doc->getElementsByTagName('body')->item(0);
        if ($body instanceof DOMElement) {
            $style = $body->getAttribute('style') ?: '';
            if (preg_match('/background(?:-image)?:\s*([^;]+)/i', $style, $m)) {
                $background['raw'] = trim($m[1]);
            }
        }
        // Check first <style> block for body/section background rules
        foreach ($doc->getElementsByTagName('style') as $styleTag) {
            $css = $styleTag->textContent;
            if (preg_match('/body\s*\{[^}]*background(?:-image)?:\s*([^;}]+)/i', $css, $m)) {
                $background['raw'] = trim($m[1]);
                break;
            }
            // Also look for section-level backgrounds
            if (preg_match('/\.hero[^{]*\{[^}]*background(?:-image)?:\s*([^;}]+)/i', $css, $m)) {
                $background['raw'] = trim($m[1]);
                break;
            }
        }
        return $background;
    }

    /** Detect repeated card patterns (same structure repeated 2+ times) */
    private function extract_cards(DOMNode $root): array
    {
        $cards = [];
        $doc = $root->ownerDocument;

        // Look for grid-like containers: divs with 2+ similar children
        $all_divs = $doc->getElementsByTagName('div');
        foreach ($all_divs as $div) {
            $children = [];
            foreach ($div->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $children[] = $child;
                }
            }
            // If container has 2-6 similar children, treat as card grid
            if (count($children) >= 2 && count($children) <= 8) {
                $first_tag = strtolower($children[0]->nodeName);
                $all_same = array_reduce($children, fn($carry, $child) =>
                    $carry && strtolower($child->nodeName) === $first_tag, true);

                if ($all_same || count($children) >= 3) {
                    foreach ($children as $child) {
                        $headings = $child->getElementsByTagName('h3');
                        $ps = $child->getElementsByTagName('p');
                        if ($headings->length > 0) {
                            $cards[] = [
                                'title' => trim($headings->item(0)->textContent),
                                'desc' => $ps->length > 0 ? trim($ps->item(0)->textContent) : '',
                            ];
                        }
                    }
                    if (count($cards) >= 2)
                        break;
                }
            }
        }
        return $cards;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // LEGACY HELPERS (Removed specialized builders to favor generic traversal)
    // ═══════════════════════════════════════════════════════════════════════════

    // ═══════════════════════════════════════════════════════════════════════════
    // GENERIC DOM WALKER & BRICKS CONVERTER
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Replaces the static build_hero, build_features, etc.
     * Walks the entire AST to convert HTML -> Bricks native JSON.
     */
    private function build_generic(array $c, string $title, DOMNode $rootNode): void
    {
        // 1. Unwrap the outer Stitch preview wrapper if it exists.
        // Stitch wraps the whole page in `<div class="relative flex min-h-screen w-full flex-col overflow-x-hidden">`.
        // This bloats the canvas. We want the actual <section>, <header>, <main> elements.
        $nodesToTraverse = [$rootNode];

        if ($rootNode->nodeName === 'body') {
            $children = [];
            foreach ($rootNode->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $children[] = $child;
                }
            }
            // If body has exactly one div child, and it looks like a full-screen wrapper, unwrap it.
            if (count($children) === 1 && $children[0]->nodeName === 'div') {
                $classStr = $children[0]->getAttribute('class');
                if (str_contains($classStr, 'min-h-screen') || str_contains($classStr, 'flex-col')) {
                    $nodesToTraverse = [];
                    foreach ($children[0]->childNodes as $grandChild) {
                        $nodesToTraverse[] = $grandChild;
                    }
                }
            } else {
                $nodesToTraverse = [];
                foreach ($rootNode->childNodes as $child) {
                    $nodesToTraverse[] = $child;
                }
            }
        }

        // 2. Traverse and build
        foreach ($nodesToTraverse as $child) {
            $this->traverse_node($child, 0);
        }
    }


    /**
     * Recursively walks a DOMNode and maps it to a Bricks element ID.
     * Extracts Tailwind classes directly from the element.
     */
    private function traverse_node(DOMNode $node, string|int $parentId): ?string
    {
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return null;
        }

        /** @var DOMElement $node */
        $tag = strtolower($node->nodeName);

        // Skip non-visual or breaking tags
        if (in_array($tag, ['script', 'style', 'meta', 'link', 'noscript', 'br', 'hr', 'head', 'title'])) {
            return null;
        }

        if (in_array($tag, ['svg', 'path', 'g', 'circle', 'rect'])) {
            return $this->create_icon_element($node, $parentId);
        }

        $elementId = $this->id();
        $bricksType = 'div';
        $settings = [];
        $childrenIds = [];

        // 1. Convert Tag to Bricks Type
        if (in_array($tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'])) {
            $bricksType = 'heading';
            $settings['tag'] = $tag;
            $settings['text'] = trim($node->textContent);
            if ($settings['text'] === '')
                return null;
        } elseif (in_array($tag, ['p', 'span', 'em', 'strong', 'small'])) {
            $bricksType = 'text-basic';
            $settings['tag'] = $tag;
            $settings['text'] = trim($node->textContent);
            if ($settings['text'] === '')
                return null;
        } elseif ($tag === 'button') {
            $bricksType = 'button';
            $settings['text'] = trim($node->textContent);
            $settings['tag'] = 'button';
        } elseif ($tag === 'a') {
            $bricksType = 'text-link';
            $settings['text'] = trim($node->textContent);
            $settings['link'] = ['type' => 'external', 'url' => $node->getAttribute('href') ?: '#'];
        } elseif ($tag === 'img') {
            $bricksType = 'image';
            $settings['tag'] = 'figure';
            $src = $node->getAttribute('src') ?: $node->getAttribute('data-src') ?: '';
            if (empty($src))
                return null;
            $settings['image'] = ['url' => $src, 'external' => true, 'alt' => $node->getAttribute('alt') ?: ''];
        } elseif ($tag === 'ul' || $tag === 'ol') {
            $bricksType = 'div';
            $settings['tag'] = $tag;
        } elseif ($tag === 'li') {
            $bricksType = 'div';
            $settings['tag'] = 'li';
        } elseif (in_array($tag, ['header', 'footer', 'main', 'section'])) {
            $bricksType = 'section';
            $settings['tag'] = $tag;
        } else {
            // Infer container/block/div from Tailwind classes
            $classString = $node->getAttribute('class') ?: '';

            if (preg_match('/\bmax-w-[a-zA-Z0-9\-]+\b/', $classString) && str_contains($classString, 'mx-auto')) {
                $bricksType = 'container';
            } elseif (str_contains($classString, 'flex-col') || str_contains($classString, 'grid')) {
                $bricksType = 'block';
            } else {
                $bricksType = 'div';
            }

            // Retain semantic HTML tags for div/block/container
            if (in_array($tag, ['nav', 'article', 'aside', 'form', 'figure'])) {
                $settings['tag'] = $tag;
            }
        }

        // 2. Extract Tailwind Classes
        $classAttr = $node->getAttribute('class') ?: '';
        if (!empty($classAttr)) {
            $classes = preg_split('/\s+/', trim($classAttr));
            $tailwindClassIds = [];
            foreach ($classes as $cls) {
                if (empty($cls))
                    continue;
                // Register the Tailwind class exactly as named so Bricks Maps it
                // We use the class name as its ID to prevent duplication
                $this->globalClasses[$cls] = [
                    'id' => $cls,
                    'name' => $cls,
                    'settings' => []
                ];
                $tailwindClassIds[] = $cls;
            }
            if (!empty($tailwindClassIds)) {
                $settings['_cssGlobalClasses'] = $tailwindClassIds;
            }
        }

        // 3. Process Children for containers
        if (in_array($bricksType, ['div', 'block', 'container', 'section'])) {
            foreach ($node->childNodes as $child) {
                $childId = $this->traverse_node($child, $elementId);
                if ($childId) {
                    $childrenIds[] = $childId;
                }
            }
        }

        $this->add_element($elementId, $bricksType, $parentId, $childrenIds, $settings);
        return $elementId;
    }

    /**
     * Extracts an icon element from SVG node
     */
    private function create_icon_element(DOMElement $svgNode, string|int $parentId): ?string
    {
        $elementId = $this->id();
        $this->add_element($elementId, 'icon', $parentId, [], [
            'icon' => ['library' => 'ionicons', 'icon' => 'ion-ios-star'], // Fallback generic icon
            '_typography' => [
                'color' => ['raw' => 'var(--primary)', 'id' => 'acss_import_primary', 'name' => 'primary']
            ]
        ], 'Icon');
        return $elementId;
    }


    // ═══════════════════════════════════════════════════════════════════════════
    // ELEMENT & CLASS FACTORIES
    // ═══════════════════════════════════════════════════════════════════════════



    // ═══════════════════════════════════════════════════════════════════════════
    // ELEMENT & CLASS FACTORIES
    // ═══════════════════════════════════════════════════════════════════════════

    private function add_element(
        string $id,
        string $name,
        string|int $parent,
        array $children,
        array $settings,
        string $label = ''
    ): void {
        $el = [
            'id' => $id,
            'name' => $name,
            'parent' => $parent === 0 ? 0 : (string) $parent,
            'children' => $children,
            'settings' => (object) $settings,
        ];
        if ($label !== '')
            $el['label'] = $label;
        $this->content[$id] = $el;
    }

    /**
     * Create a named global class, register it, and return its ID.
     * If $define_settings is false (e.g. card repeated), skip settings to avoid duplicates.
     */
    private function make_class(string $name, array $settings, bool $define = true): string
    {
        // check if already registered under this name
        foreach ($this->globalClasses as $cls) {
            if ($cls['name'] === $this->slug_prefix($name)) {
                return $cls['id'];
            }
        }

        $id = $this->id();
        $this->globalClasses[$id] = [
            'id' => $id,
            'name' => $this->slug_prefix($name),
            'settings' => $define && !empty($settings) ? $settings : [],
        ];
        return $id;
    }


    private function slug_prefix(string $name): string
    {
        $prefix = preg_replace('/[^a-z0-9]/', '-', strtolower($this->sectionSlug));
        // If name already starts with the prefix, don't double-prefix
        if (str_starts_with($name, $prefix))
            return $name;
        return $prefix . '-' . ltrim($name, '-');
    }

    private function id(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        do {
            $id = '';
            for ($i = 0; $i < 6; $i++) {
                $id .= $chars[random_int(0, 35)];
            }
        } while (isset($this->content[$id]));
        return $id;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function find_tags(DOMNode $root, array $tags): array
    {
        $found = [];
        $doc = $root->ownerDocument;
        foreach ($tags as $tag) {
            foreach ($doc->getElementsByTagName($tag) as $node) {
                $found[] = $node;
            }
        }
        return $found;
    }

    private function make_placeholder_cards(int $n): array
    {
        $cards = [];
        $titles = ['Fast Performance', 'Easy to Use', 'Built to Scale', 'Always Secure', 'Great Support'];
        $descs = [
            'Built for speed from the ground up, reducing load times dramatically.',
            'Intuitive design that gets out of your way so you can focus on what matters.',
            'Scales effortlessly from startup to enterprise without re-architecting.',
        ];
        for ($i = 0; $i < $n; $i++) {
            $cards[] = ['title' => $titles[$i] ?? "Feature $i", 'desc' => $descs[$i] ?? ''];
        }
        return $cards;
    }
}
