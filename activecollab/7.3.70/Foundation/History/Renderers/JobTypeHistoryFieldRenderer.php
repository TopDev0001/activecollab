<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\History\Renderers;

use JobTypes;
use Language;

class JobTypeHistoryFieldRenderer implements HistoryFieldRendererInterface
{
    public function render(
        $old_value,
        $new_value,
        Language $language
    ): ?string
    {
        if ($old_value && $new_value) {
            return lang(
                'Job type changed from <b>:old_value</b> to <b>:new_value</b>',
                [
                    'old_value' => JobTypes::getNameById($old_value),
                    'new_value' => JobTypes::getNameById($new_value),
                ],
                true,
                $language
            );
        }

        if ($new_value) {
            return lang(
                'Job type set to <b>:new_value</b>',
                [
                    'new_value' => JobTypes::getNameById($new_value),
                ],
                true,
                $language
            );
        }

        if ($old_value) {
            return lang('Job type removed', null, true, $language);
        }

        return null;
    }
}
