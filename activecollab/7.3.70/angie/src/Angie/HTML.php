<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie;

use Closure;
use simple_html_dom;

/**
 * HTML tag generator class.
 *
 * @package angie.library
 */
final class HTML
{
    /**
     * Open HTML tag.
     *
     * @param  string              $name
     * @param  array               $attributes
     * @param  Closure|string|null $content
     * @return string
     */
    public static function openTag($name, $attributes = null, $content = null)
    {
        if ($attributes) {
            $result = "<$name";

            foreach ($attributes as $k => $v) {
                if ($k) {
                    if (is_bool($v)) {
                        if ($v) {
                            $result .= " $k";
                        }
                    } else {
                        $result .= ' ' . $k . '="' . ($v ? clean($v) : $v) . '"';
                    }
                }
            }

            $result .= '>';
        } else {
            $result = "<$name>";
        }

        if ($content) {
            if ($content instanceof Closure) {
                $result .= $content();
            } else {
                $result .= $content;
            }

            $result .= "</$name>";
        }

        return $result;
    }

    // ---------------------------------------------------
    //  Converters
    // ---------------------------------------------------

    /**
     * Convert HTML to plain text (email style).
     *
     * @param  string $html
     * @return string
     */
    public static function toPlainText($html)
    {
        $plain = (string) $html;

        // strip slashes
        $plain = (string) trim(stripslashes($plain));

        // strip unnecessary characters
        $plain = (string) preg_replace([
            "/\r/", // strip carriage returns
            "/<script[^>]*>.*?<\/script>/si", // strip immediately, because we don't need any data from it
            "/<style[^>]*>.*?<\/style>/is", // strip immediately, because we don't need any data from it
            '/style=".*?"/',   //was: '/style=\"[^\"]*/'
        ], '', $plain);

        // entities to convert (this is not a definite list)
        $entities = [
            ' ' => ['&nbsp;', '&#160;'],
            '"' => ['&quot;', '&rdquo;', '&ldquo;', '&#8220;', '&#8221;', '&#147;', '&#148;'],
            '\'' => ['&apos;', '&rsquo;', '&lsquo;', '&#8216;', '&#8217;'],
            '>' => ['&gt;'],
            '<' => ['&lt;'],
            '&' => ['&amp;', '&#38;'],
            '(c)' => ['&copy;', '&#169;'],
            '(R)' => ['&reg;', '&#174;'],
            '(tm)' => ['&trade;', '&#8482;', '&#153;'],
            '--' => ['&mdash;', '&#151;', '&#8212;'],
            '-' => ['&ndash;', '&minus;', '&#8211;', '&#8722;'],
            '*' => ['&bull;', '&#149;', '&#8226;'],
            '£' => ['&pound;', '&#163;'],
            'EUR' => ['&euro;', '&#8364;'],
        ];

        // convert specified entities
        foreach ($entities as $character => $entity) {
            $plain = (string) str_replace_utf($entity, $character, $plain);
        }

        // strip other not previously converted entities
        $plain = (string) preg_replace([
            '/&[^&;]+;/si',
        ], '', $plain);

        // <p> converts to 2 newlines
        $plain = (string) preg_replace('/<p[^>]*>/i', "\n\n", $plain); // <p>

        // uppercase html elements
        $plain = (string) preg_replace_callback('/<h[123456][^>]*>(.*?)<\/h[123456]>/i', function ($matches) {
            return "\n\n" . strtoupper_utf($matches[1]) . "\n\n";
        }, $plain); // <h1-h6>

        $plain = (string) preg_replace_callback(['/<b[^>]*>(.*?)<\/b>/i', '/<strong[^>]*>(.*?)<\/strong>/i'], function ($matches) {
            return strtoupper_utf($matches[1]);
        }, $plain); // <b> <strong>

        // deal with italic elements
        $plain = (string) preg_replace(['/<i[^>]*>(.*?)<\/i>/i', '/<em[^>]*>(.*?)<\/em>/i'], '_\\1_', $plain); // <i> <em>

        // elements that convert to 2 newlines
        $plain = (string) preg_replace(['/(<ul[^>]*>|<\/ul>)/i', '/(<ol[^>]*>|<\/ol>)/i', '/(<table[^>]*>|<\/table>)/i'], "\n\n", $plain); // <ul> <ol> <table>

        // elements that convert to single newline
        $plain = (string) preg_replace(['/<br[^>]*>/i', '/(<tr[^>]*>|<\/tr>)/i'], "\n", $plain); // <br> <tr>

        // <hr> converts to -----------------------
        $plain = (string) preg_replace('/<hr[^>]*>/i', "\n-------------------------\n", $plain); // <hr>

        // other table tags
        $plain = (string) preg_replace('/<td[^>]*>(.*?)<\/td>/i', "\\1\n", $plain); // <td>
        $plain = (string) preg_replace_callback('/<th[^>]*>(.*?)<\/th>/i', function ($matches) {
            return strtoupper_utf($matches[1]) . "\n";
        }, $plain); // <th>

        // list elements
        $plain = (string) preg_replace('/<li[^>]*>(.*?)<\/li>/i', "* \\1\n", $plain); // <li>with content</li>
        $plain = (string) preg_replace('/<li[^>]*>/i', "\n* ", $plain); // <li />

        // handle anchors
        $plain = (string) preg_replace_callback('/<a [^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/i', function ($matches) {
            return HTML::toPlainTextProcessUrl($matches[1], $matches[2]);
        }, $plain); // <li />

        // handle blockquotes
        $plain = (string) preg_replace_callback('/<blockquote[^>]*>(.*?)<\/blockquote>/is', function ($blockquote_content) {
            $blockquote_content = isset($blockquote_content[1]) ? $blockquote_content[1] : '';

            $lines = (array) explode("\n", $blockquote_content);
            $return = [];
            if (is_foreachable($lines)) {
                foreach ($lines as $line) {
                    $return[] = '> ' . $line;
                }
            }

            return "\n\n" . implode("\n", $return) . "\n\n";
        }, $plain);

        // strip other tags
        $plain = (string) strip_tags($plain);

        // clean up unneccessary newlines
        $plain = (string) preg_replace("/\n\s+\n/", "\n\n", $plain);
        $plain = (string) preg_replace("/[\n]{3,}/", "\n\n", $plain);

        return trim($plain);
    }

    /**
     * This function is used as a callback in html_to_text function to process
     * links found in the text.
     *
     * @param  string $url
     * @param  string $text
     * @return string
     */
    public static function toPlainTextProcessUrl($url, $text)
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $text == $url ? $url : "$text [$url]";
        } elseif (str_starts_with($url, 'mailto:')) {
            $email_address = substr($url, 7);

            return $text == $email_address ? $email_address : $text . ' [' . $email_address . ']';
        } else {
            return $text;
        }
    }

    /**
     * Prepare Simple HTML DOM instance from input $html.
     *
     * @param  string               $html
     * @return bool|simple_html_dom
     */
    public static function getDOM($html)
    {
        $dom = new simple_html_dom(null, true, true, 'UTF-8', "\r\n");
        if (empty($html)) {
            $dom->clear();

            return false;
        }
        $dom->load($html, true, true);

        return $dom;
    }
}
