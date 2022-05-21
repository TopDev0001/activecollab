<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Http\Response\StatusResponse;

/**
 * @package Angie\Http\Response\StatusResponse
 */
interface StatusResponseInterface
{
    /**
     * @return int
     */
    public function getStatusCode();

    /**
     * @return string
     */
    public function getReasonPhrase();

    /**
     * @return mixed
     */
    public function getPayload();
}
