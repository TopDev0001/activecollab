<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use Angie\Globalization;

function smarty_modifier_money(
    $content,
    Currency $currency = null,
    Language $language = null,
    bool $with_currency_code = false,
    bool $round = false
): string
{
    return Globalization::formatMoney(
        $content,
        $currency,
        $language,
        $with_currency_code,
        $round
    );
}
