<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\HtmlCleaner;

use ActiveCollab\Foundation\Text\HtmlCleaner\AllowedTag\AllowedTagInterface;

interface HtmlCleanerInterface
{
    const DEFAULT_ALLOWED_TAGS = [
        'br' => [],
        'div' => [
            'class',
            'align',
        ],
        'span' => [
            'class',
            'align',
            'style',
        ],
        'a' => [
            'href',
            'title',
            'class',
            'align',
            'target',
        ],
        'code' => [],
        'img' => [
            'src',
            'alt',
            'title',
            'class',
            'align',
        ],
        'p' => [
            'class',
            'align',
            'style',
        ],
        'blockquote' => [
            'class',
        ],
        'ul' => [
            'class',
            'align',
        ],
        'ol' => [
            'class',
            'align',
        ],
        'li' => [
            'class',
            'align',
        ],
        'b' => [],
        'strong' => [],
        'i' => [],
        'em' => [],
        'u' => [],
        'strike' => [
            'class',
            'style',
        ],
        'del' => [],
        'pre' => [],
        'table' => [],
        'thead' => [],
        'tbody' => [],
        'tfoot' => [],
        'tr' => [],
        'td' => [
            'align',
            'class',
            'colspan',
            'rowspan',
        ],
        'th' => [
            'align',
        ],
        'h1' => [
            'align',
        ],
        'h2' => [
            'align',
        ],
        'h3' => [
            'align',
        ],
    ];

    const DEFAULT_ALLOWED_INLINE_STYLES = [
        'text-decoration',
    ];

    public function cleanUp(string $html, callable $extra_dom_manipulation = null): string;

    /**
     * @return AllowedTagInterface[]
     */
    public function getAllowedTags(): array;
    public function isTagAllowed(string $tag_name): bool;
    public function isTagAttributeAllowed(string $tag_name, string $attribute_name): bool;
    public function allowTag(AllowedTagInterface $allowed_tag): void;
}
