<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Model\Message;

use Message;
use User;

abstract class SystemMessage extends Message implements SystemMessageInterface
{
    public function canDelete(User $user): bool
    {
        return false;
    }

    public function canEdit(User $user): bool
    {
        return false;
    }
}
