<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Collections\ActivityLog\Traits;

trait IRangeDates
{
    protected string $dates;

    public function &setDates(string $dates): self
    {
        $this->dates = $dates;

        return $this;
    }
}
