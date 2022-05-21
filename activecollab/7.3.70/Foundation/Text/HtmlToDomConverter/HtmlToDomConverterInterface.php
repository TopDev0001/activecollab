<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\HtmlToDomConverter;

use simple_html_dom;

interface HtmlToDomConverterInterface
{
    public function htmlToDom(string $html): simple_html_dom;
}
