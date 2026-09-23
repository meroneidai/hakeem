<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Sanitizes admin-authored HTML for public rendering without a WYSIWYG package.
 */
class SafeHtml
{
    /**
     * @var list<string>
     */
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'h2', 'h3', 'h4',
        'ul', 'ol', 'li', 'a', 'span', 'blockquote', 'div', 'font',
    ];

    /**
     * @var list<string>
     */
    private const STYLE_PROPERTIES = [
        'color', 'background-color', 'font-size', 'font-family', 'text-align',
        'font-weight', 'font-style', 'text-decoration',
    ];

    public static function sanitize(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;

        $allowed = '<'.implode('><', self::ALLOWED_TAGS).'>';
        $stripped = strip_tags($html, $allowed);

        $dom = new DOMDocument;
        $dom->encoding = 'UTF-8';
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="safe-html-root">'.$stripped.'</div></body></html>',
        );
        libxml_clear_errors();

        $root = $dom->getElementById('safe-html-root')
            ?: $dom->getElementsByTagName('div')->item(0);

        if (! $root) {
            return '';
        }

        static::scrubNode($root);

        $output = '';

        foreach ($root->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        return trim($output);
    }

    public static function render(?string $html): string
    {
        $clean = static::sanitize($html);

        if ($clean === '') {
            return '';
        }

        if (! preg_match('/<[a-z][\s\S]*>/i', $clean)) {
            return nl2br(e($clean), false);
        }

        return $clean;
    }

    public static function isEmpty(?string $html): bool
    {
        return trim(html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8')) === '';
    }

    private static function scrubNode(DOMNode $node): void
    {
        $children = [];

        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);

                    continue;
                }

                static::scrubAttributes($child, $tag);
                static::scrubNode($child);
            }
        }
    }

    private static function scrubAttributes(DOMElement $element, string $tag): void
    {
        $keep = [];

        if ($element->hasAttribute('style')) {
            $style = static::safeStyle($element->getAttribute('style'));

            if ($style !== null) {
                $keep['style'] = $style;
            }
        }

        if ($tag === 'a') {
            $href = static::safeHref($element->getAttribute('href'));

            if ($href !== null) {
                $keep['href'] = $href;
            }
        }

        if ($tag === 'font') {
            $face = trim($element->getAttribute('face'));

            if ($face !== '' && preg_match('/^[a-z0-9 ,._-]+$/i', $face)) {
                $keep['face'] = $face;
            }

            $size = $element->getAttribute('size');

            if (in_array($size, ['1', '2', '3', '4', '5', '6', '7'], true)) {
                $keep['size'] = $size;
            }

            $color = static::safeColor($element->getAttribute('color'));

            if ($color !== null) {
                $keep['color'] = $color;
            }
        }

        while ($element->hasAttributes()) {
            $element->removeAttribute($element->attributes->item(0)->nodeName);
        }

        foreach ($keep as $name => $value) {
            $element->setAttribute($name, $value);
        }
    }

    private static function safeHref(string $href): ?string
    {
        $href = trim($href);

        if ($href === '') {
            return null;
        }

        if (str_starts_with($href, '/') && ! str_starts_with($href, '//')) {
            return $href;
        }

        if (preg_match('/^(https?:|mailto:)/i', $href) === 1) {
            return $href;
        }

        return null;
    }

    private static function safeColor(string $color): ?string
    {
        $color = trim($color);

        if ($color === '') {
            return null;
        }

        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color) === 1) {
            return $color;
        }

        if (preg_match('/^[a-z]+$/i', $color) === 1) {
            return $color;
        }

        return null;
    }

    private static function safeStyle(string $style): ?string
    {
        $parts = [];

        foreach (explode(';', $style) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);

            if (! in_array($property, self::STYLE_PROPERTIES, true)) {
                continue;
            }

            $lower = strtolower($value);

            if (str_contains($lower, 'url(') || str_contains($lower, 'expression') || str_contains($lower, 'javascript')) {
                continue;
            }

            if (preg_match('/^[a-z0-9#%,.\s\'"-]+$/i', $value) !== 1) {
                continue;
            }

            $parts[] = $property.': '.$value;
        }

        return $parts === [] ? null : implode('; ', $parts);
    }
}
