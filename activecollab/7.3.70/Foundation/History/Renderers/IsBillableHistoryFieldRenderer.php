<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\History\Renderers;

use Language;

class IsBillableHistoryFieldRenderer implements HistoryFieldRendererInterface
{
    public function render(
        $old_value,
        $new_value,
        Language $language
    ): ?string
    {
        if ($new_value) {
            return lang('Marked as billable', null, true, $language);
        }

        return lang('Marked as non-billable', null, true, $language);
    }
}
