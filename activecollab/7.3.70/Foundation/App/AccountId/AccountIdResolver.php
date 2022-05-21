<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\App\AccountId;

use AngieApplication;

class AccountIdResolver implements AccountIdResolverInterface
{
    public function getAccountId(): int
    {
        return AngieApplication::getAccountId();
    }
}
