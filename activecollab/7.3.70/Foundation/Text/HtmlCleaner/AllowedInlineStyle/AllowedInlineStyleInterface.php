<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\HtmlCleaner\AllowedInlineStyle;

use ActiveCollab\Foundation\Text\HtmlCleaner\AllowedElement\AllowedElementInterface;

interface AllowedInlineStyleInterface extends AllowedElementInterface
{
    public function getCssRule(): string;
}
