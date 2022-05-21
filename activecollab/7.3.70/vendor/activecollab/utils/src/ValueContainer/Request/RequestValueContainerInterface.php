<?php

/*
 * This file is part of the Active Collab Utils project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ValueContainer\Request;

use ActiveCollab\ValueContainer\ValueContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RequestValueContainerInterface extends ValueContainerInterface
{
    public function getRequest(): ?ServerRequestInterface;
    public function setRequest(?ServerRequestInterface $request): RequestValueContainerInterface;
}
