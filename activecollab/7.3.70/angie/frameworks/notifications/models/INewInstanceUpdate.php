<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

trait INewInstanceUpdate
{
    public function onObjectUpdateFlags(array &$updates)
    {
        $updates['new_instance'][] = $this->getId();
    }
}
