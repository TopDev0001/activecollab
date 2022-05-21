<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\Context;

use ActiveCollab\Foundation\Urls\PermalinkInterface;

interface RoutingContextInterface extends PermalinkInterface
{
    public function getRoutingContext(): string;
    public function getRoutingContextParams(): array;
}
