<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\DefaultCurrencyResolver;

use Currency;

interface DefaultCurrencyResolverInterface
{
    public function getDefaultCurrency(): Currency;
    public function getDefaultCurrencyId(): int;
}
