<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\HtmlCleaner\AllowedInlineStyle;

class AllowedInlineStyle implements AllowedInlineStyleInterface
{
    private string $css_rule;

    public function __construct(string $css_rule)
    {
        $this->css_rule = $css_rule;
    }

    public function getCssRule(): string
    {
        return $this->css_rule;
    }
}
