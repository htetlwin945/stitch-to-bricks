import re
import sys

def refactor():
    with open('f:/bricks-ai/includes/class-stb-converter.php', 'r', encoding='utf-8') as f:
        content = f.read()
    
    # 1. Remove CF_SHARED_CLASSES and CF_SPACING and usedCfClasses
    content = re.sub(r'// ─── Core Framework shared global classes.*?private const CF_SPACING[^\;]+\;', '', content, flags=re.DOTALL)
    content = re.sub(r'private array \$usedCfClasses = \[\];.*?// CF shared classes referenced in this output\n', '', content)
    
    # 2. Remove $this->usedCfClasses = []; in convert()
    content = re.sub(r'\$this->usedCfClasses = \[\];\n', '', content)
    
    # 3. Remove CF shared classes addition block in convert()
    cf_block = r'// ── Add referenced CF shared classes ──────────────────────────────────\s*foreach \(\$this->usedCfClasses as \$id\) \{.*?\n\s*\}\n'
    content = re.sub(cf_block, '', content, flags=re.DOTALL)
    
    # 4. Remove traverse_node completely, we will replace it
    content = re.sub(r'/\*\*\s*\*\s*Recursively walks a DOMNode and maps it to a Bricks element ID.*?return \$elementId;\n    }\n', '', content, flags=re.DOTALL)

    # 5. Remove map_inline_styles_to_cf and px_to_cf_space
    content = re.sub(r'/\*\*\s*\*\s*Map inline CSS from Stitch HTML directly into Core Framework Bricks properties/variables.*?\n    }\n\n    /\*\*\s*\*\s*Converts a pixel/rem string to the closest Core Framework spacing variable.*?\n    }\n', '', content, flags=re.DOTALL)

    # 6. Remove cf_class
    content = re.sub(r'/\*\*\s*Return array of IDs for a CF shared class \(marks as used\)\s*\*/\s*private function cf_class\(string \$id\): array\s*\{\s*\$this->usedCfClasses\[\] = \$id;\s*return \[\$id\];\s*\}\n', '', content)

    # 7. Remove build_cta_buttons completely (it uses cf_class)
    content = re.sub(r'/\*\*\s*Build CTA button elements, return array of element IDs\s*\*/\s*private function build_cta_buttons.*?return \$ids;\s*\}\n', '', content, flags=re.DOTALL)
    
    # 8. Remove build_intro_block
    content = re.sub(r'/\*\*\s*Build a standard CF intro block \(tagline → heading → subheading\)\s*\*/\s*private function build_intro_block.*?return \$introId;\s*\}\n', '', content, flags=re.DOTALL)

    # Now define the new traverse_node
    new_traverse_node = """
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
            if ($settings['text'] === '') return null;
        } elseif (in_array($tag, ['p', 'span', 'em', 'strong', 'small'])) {
            $bricksType = 'text-basic';
            $settings['tag'] = $tag;
            $settings['text'] = trim($node->textContent);
            if ($settings['text'] === '') return null;
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
            if (empty($src)) return null;
            $settings['image'] = ['url' => $src, 'external' => true, 'alt' => $node->getAttribute('alt') ?: ''];
        } elseif ($tag === 'ul' || $tag === 'ol') {
            $bricksType = 'div';
            $settings['tag'] = $tag;
        } elseif ($tag === 'li') {
            $bricksType = 'div';
            $settings['tag'] = 'li';
        } elseif ($tag === 'nav') {
            $bricksType = 'block';
            $settings['tag'] = 'nav';
        } else {
            $bricksType = 'div';
        }

        // 2. Extract Tailwind Classes
        $classAttr = $node->getAttribute('class') ?: '';
        if (!empty($classAttr)) {
            $classes = preg_split('/\s+/', trim($classAttr));
            $tailwindClassIds = [];
            foreach ($classes as $cls) {
                if (empty($cls)) continue;
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
"""
    
    # Insert new traverse_node right after build_generic
    content = content.replace('    /**\n     * Extracts an icon element from SVG node\n     */', new_traverse_node + '\n    /**\n     * Extracts an icon element from SVG node\n     */')
    
    with open('f:/bricks-ai/includes/class-stb-converter.php', 'w', encoding='utf-8') as f:
        f.write(content)

refactor()
