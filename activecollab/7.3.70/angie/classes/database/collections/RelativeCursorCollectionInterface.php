<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

interface RelativeCursorCollectionInterface extends CursorCollectionInterface
{
    public function getLastId(): ?int;
}
