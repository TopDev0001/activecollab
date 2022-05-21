<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Model\FeaturePointer;

use User;

interface FeaturePointerInterface
{
    public function getDescription(): string;

    public function shouldShow(User $user): bool;
}
