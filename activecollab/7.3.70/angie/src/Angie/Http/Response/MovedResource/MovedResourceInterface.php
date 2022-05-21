<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Http\Response\MovedResource;

/**
 * @package Angie\Http\Response\MovedResource
 */
interface MovedResourceInterface
{
    /**
     * @return string
     */
    public function getUrl();

    /**
     * @return bool
     */
    public function isMovedPermanently();

    /**
     * @return int
     */
    public function getStatusCode();
}
