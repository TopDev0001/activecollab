<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\MatchedRoute;

interface MatchedEntityInterface extends MatchedRouteInterface
{
    public function getEntityName(): string;
    public function getEntityId(): int;
}
