<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\MatchedRoute;

interface MatchedRouteInterface
{
    public function getRouteName(): string;
    public function getUrlParams(): array;
    public function getModule(): string;
    public function getController(): string;
    public function getAction(): string;
    public function getArguments(): array;
}
