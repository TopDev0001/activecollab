<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Utils\UserDateResolver;

use DateValue;
use User;

interface UserDateResolverInterface
{
    public function getUserDate(User $user): DateValue;
}
