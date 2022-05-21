<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\History\Renderers;

use Language;

class IsHiddenFromClientsHistoryFieldRenderer implements HistoryFieldRendererInterface
{
    public function render(
        $old_value,
        $new_value,
        Language $language
    ): ?string
    {
        if ($new_value) {
            return lang('Marked as hidden from clients', null, true, $language);
        } else {
            return lang('No longer hidden from clients', null, true, $language);
        }
    }
}
