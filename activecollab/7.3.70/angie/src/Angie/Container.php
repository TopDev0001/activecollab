<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie;

use Interop\Container\ContainerInterface;
use InvalidArgumentException;
use Pimple\Container as PimpleContainer;

/**
 * @package Angie
 */
class Container extends PimpleContainer implements ContainerInterface
{
    public function get($id)
    {
        if (!$this->offsetExists($id)) {
            throw new InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $id));
        }

        return $this->offsetGet($id);
    }

    public function has($id)
    {
        return $this->offsetExists($id);
    }
}
