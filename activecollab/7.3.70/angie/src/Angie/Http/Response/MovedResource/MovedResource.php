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
class MovedResource implements MovedResourceInterface
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var bool
     */
    private $is_moved_permanently;

    /**
     * @param string $url
     * @param bool   $is_moved_permanently
     */
    public function __construct($url, $is_moved_permanently = false)
    {
        $this->url = $url;
        $this->is_moved_permanently = (bool) $is_moved_permanently;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function isMovedPermanently()
    {
        return $this->is_moved_permanently;
    }

    public function getStatusCode()
    {
        return $this->isMovedPermanently() ? 301 : 302;
    }
}
