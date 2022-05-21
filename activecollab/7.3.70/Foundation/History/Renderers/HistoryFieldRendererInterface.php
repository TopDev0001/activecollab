<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\History\Renderers;

use Language;

interface HistoryFieldRendererInterface
{
    public function render(
        $old_value,
        $new_value,
        Language $language
    ): ?string;
}
