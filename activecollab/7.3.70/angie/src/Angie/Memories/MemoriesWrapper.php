<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Memories;

use ActiveCollab\Memories\Adapter\MySQL;
use ActiveCollab\Memories\Memories;
use mysqli;

class MemoriesWrapper implements MemoriesWrapperInterface
{
    private $memories;

    public function __construct(mysqli &$link)
    {
        $this->memories = new Memories(new MySQL($link, false));
    }

    public function &getInstance(): Memories
    {
        return $this->memories;
    }

    public function get($key, $if_not_found_return = null, $use_cache = true)
    {
        return $this->memories->get($key, $if_not_found_return, $use_cache);
    }

    public function set($key, $value = null, $bulk = false)
    {
        return $this->memories->set($key, $value, $bulk);
    }

    public function forget($key, $bulk = false): void
    {
        $this->memories->forget($key, $bulk);
    }
}
