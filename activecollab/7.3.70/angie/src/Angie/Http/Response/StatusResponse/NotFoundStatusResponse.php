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
class NotFoundStatusResponse extends StatusResponse
{
    /**
     * @param string $reason_phrase
     * @param mixed  $payload
     */
    public function __construct($reason_phrase = 'Not Found.', $payload = null)
    {
        parent::__construct(404, $reason_phrase, $payload);
    }
}
