<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Expander\Expansion;

class ReplaceElementExpansion extends UrlExpansion implements ReplaceElementExpansionInterface
{
    private string $replacement_html;

    public function __construct(string $replacement_html)
    {
        $this->replacement_html = $replacement_html;
    }

    public function getReplacementHtml(): string
    {
        return $this->replacement_html;
    }
}
