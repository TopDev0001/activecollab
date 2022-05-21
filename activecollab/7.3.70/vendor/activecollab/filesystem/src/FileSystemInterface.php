<?php

/*
 * This file is part of the Active Collab File System.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\FileSystem;

use ActiveCollab\FileSystem\Adapter\AdapterInterface;

/**
 * @package ActiveCollab\FileSystem
 */
interface FileSystemInterface extends AdapterInterface
{
    /**
     * Return user Adapter instance.
     *
     * @return \ActiveCollab\FileSystem\Adapter\AdapterInterface
     */
    public function &getAdapter();
}
