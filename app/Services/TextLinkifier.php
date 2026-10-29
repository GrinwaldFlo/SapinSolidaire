<?php

namespace App\Services;

/**
 * Turns free text (e.g. an admin rejection comment) into escaped HTML where
 * URLs (http://, https:// or www.) become clickable links.
 *
 * Trailing punctuation is kept out of the link (« voir https://site.ch. »).
 */
class TextLinkifier
{
    private const URL_PATTERN = '~(\b(?:https?://|www\.)[^\s<>"]*[^\s<>".,;:!?)\]\'»])~iu';

    /**
     * Escaped like Blade {{ }} (quotes included), for pages.
     */
    public static function toHtml(?string $text, ?string $class = null): string
    {
        return self::render($text, $class, ENT_QUOTES);
    }

    /**
     * Line breaks as <br> and quotes kept readable, for contexts without CSS (HTML emails).
     */
    public static function toMultilineHtml(?string $text, ?string $class = null): string
    {
        return nl2br(self::render($text, $class, ENT_NOQUOTES));
    }

    private static function render(?string $text, ?string $class, int $escapeFlags): string
    {
        // Invalid UTF-8 makes preg_split (/u) fail: replace bad bytes instead.
        $text = mb_scrub((string) $text, 'UTF-8');
        $parts = preg_split(self::URL_PATTERN, $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        $html = '';

        foreach ($parts as $index => $part) {
            // Odd indexes are the captured URLs, even ones the text around them.
            $html .= $index % 2 === 1 ? self::link($part, $class) : htmlspecialchars($part, $escapeFlags, 'UTF-8');
        }

        return $html;
    }

    private static function link(string $url, ?string $class): string
    {
        $href = str_starts_with(strtolower($url), 'www.') ? 'https://'.$url : $url;
        $classAttribute = $class !== null ? ' class="'.e($class).'"' : '';

        return '<a href="'.e($href).'"'.$classAttribute.' target="_blank" rel="noopener noreferrer">'.e($url).'</a>';
    }
}
