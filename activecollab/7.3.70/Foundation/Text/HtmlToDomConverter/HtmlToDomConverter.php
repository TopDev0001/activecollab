<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\HtmlToDomConverter;

use simple_html_dom;

class HtmlToDomConverter implements HtmlToDomConverterInterface
{
    public function htmlToDom(string $html): simple_html_dom
    {
        $dom = new simple_html_dom(
            null,
            true,
            true,
            'UTF-8',
            "\r\n"
        );
        $dom->load($html, true, true);

        if ($dom === false) {
            $dom = new simple_html_dom(
                null,
                true,
                true,
                'UTF-8',
                "\r\n"
            );
            $dom->load(nl2br($html), true, true);
        }

        return $dom;
    }
}
