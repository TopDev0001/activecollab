<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;

class DBParentColumn extends DBRelatedObjectColumn
{
    public function __construct(bool $add_key = true, bool $can_be_null = true)
    {
        parent::__construct('parent', $add_key, $can_be_null);
    }

    public function addedToTable(): void
    {
        $this->table->addModelTrait(
            [
                RoutingContextInterface::class => null,
                IChild::class => IChildImplementation::class,
            ]
        );

        parent::addedToTable();
    }
}
