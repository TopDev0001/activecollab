<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

abstract class CompositeCursorCollection extends DataObjectCollection implements CursorCollectionInterface
{
    public function getNextCursor()
    {
        return $this->getCursorCollection()->getNextCursor();
    }

    abstract public function getCursorCollection(): CursorModelCollection;
}
