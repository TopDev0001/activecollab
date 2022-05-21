<?php

/*
 * This file is part of the Active Collab ContainerAccess project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ContainerAccess;

use Interop\Container\ContainerInterface;

/**
 * @package ActiveCollab\ContainerAccess
 */
interface ContainerAccessInterface
{
    /**
     * @return bool
     */
    public function hasContainer();

    /**
     * Return container instance.
     *
     * @return ContainerInterface
     */
    public function &getContainer();

    /**
     * @param  ContainerInterface $container
     * @return $this
     */
    public function &setContainer(ContainerInterface &$container);
}
