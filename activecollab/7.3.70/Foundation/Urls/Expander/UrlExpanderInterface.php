<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Expander;

use ActiveCollab\Foundation\Models\IdentifiableInterface;
use simple_html_dom;

interface UrlExpanderInterface
{
    public function expandUrlsInDom(
        simple_html_dom $dom,
        IdentifiableInterface $context,
        string $display
    );
}
