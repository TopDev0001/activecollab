<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\Mapper\Factory;

use ActiveCollab\Foundation\Urls\Router\Mapper\RouteMapperInterface;

interface RouteMapperFactoryInterface
{
    public function createMapper(): RouteMapperInterface;
}
