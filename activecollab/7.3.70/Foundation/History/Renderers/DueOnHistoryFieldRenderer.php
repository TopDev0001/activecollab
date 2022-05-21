<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\History\Renderers;

use DateValue;
use Language;

class DueOnHistoryFieldRenderer implements HistoryFieldRendererInterface
{
    public function render(
        $old_value,
        $new_value,
        Language $language
    ): ?string
    {
        if ($old_value && $new_value) {
            return lang(
                'Due date changed from <b>:old_value</b> to <b>:new_value</b>',
                [
                    'old_value' => DateValue::makeFromString($old_value)->formatForUser(null, 0, $language),
                    'new_value' => DateValue::makeFromString($new_value)->formatForUser(null, 0, $language),
                ],
                true,
                $language
            );
        }

        if ($new_value) {
            return lang(
                'Due date set to <b>:new_value</b>',
                [
                    'new_value' => DateValue::makeFromString($new_value)->formatForUser(null, 0, $language),
                ],
                true,
                $language
            );
        }

        if ($old_value) {
            return lang('Due date removed', null, true, $language);
        }

        return null;
    }
}
