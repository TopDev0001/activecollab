<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\Compilers;

use ActiveCollab\Foundation\Urls\Router\Mapper\RouteMapperInterface;

interface CompilerInterface
{
    public function compile(RouteMapperInterface $mapper, string $file_path): void;
}
