<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Utils\UserDateResolver;

use ActiveCollab\CurrentTimestamp\CurrentTimestampInterface;
use Angie\Globalization;
use DateValue;
use User;

class UserDateResolver implements UserDateResolverInterface
{
    private CurrentTimestampInterface $current_timestamop;

    public function __construct(CurrentTimestampInterface $current_timestamop)
    {
        $this->current_timestamop = $current_timestamop;
    }

    public function getUserDate(User $user): DateValue
    {
        return new DateValue(
            $this->current_timestamop->getCurrentTimestamp() + Globalization::getUserGmtOffset($user)
        );
    }
}
