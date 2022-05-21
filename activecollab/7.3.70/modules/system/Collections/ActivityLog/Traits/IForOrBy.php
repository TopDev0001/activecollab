<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Collections\ActivityLog\Traits;

use User;

trait IForOrBy
{
    private User $for_or_by;

    protected function &getForOrBy(): User
    {
        return $this->for_or_by;
    }

    public function &setForOrBy(User $for_or_by): self
    {
        $this->for_or_by = $for_or_by;

        return $this;
    }
}
