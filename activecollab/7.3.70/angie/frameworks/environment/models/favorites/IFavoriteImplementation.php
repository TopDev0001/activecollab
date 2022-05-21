<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

trait IFavoriteImplementation
{
    public function registerIFavoriteImplementation()
    {
        $this->registerEventHandler(
            'on_before_delete',
            function () {
                Favorites::deleteByParent($this);
            }
        );
    }

    abstract protected function registerEventHandler(string $event, callable $handler): void;
}
