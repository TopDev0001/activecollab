<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Collections\ActivityLog\Traits;

use ApplicationObject;

trait ILogsIn
{
    protected ApplicationObject $in;

    public function &setIn(ApplicationObject $in): self
    {
        $this->in = $in;

        return $this;
    }
}
