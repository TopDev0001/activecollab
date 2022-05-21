<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\History\Renderers;

use Language;

class BodyHistoryFieldRenderer implements HistoryFieldRendererInterface
{
    public function render(
        $old_value,
        $new_value,
        Language $language
    ): ?string
    {
        if ($new_value && $old_value) {
            return lang('Description updated', null, true, $language);
        } elseif ($new_value) {
            return lang('Description added', null, true, $language);
        } elseif ($old_value) {
            return lang('Description removed', null, true, $language);
        }

        return null;
    }
}
