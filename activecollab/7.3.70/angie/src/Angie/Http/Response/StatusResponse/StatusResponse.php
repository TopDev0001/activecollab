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
class StatusResponse implements StatusResponseInterface
{
    /**
     * @var int
     */
    private $status_code;

    /**
     * @var string
     */
    private $reason_phrase;

    /**
     * @var mixed
     */
    private $payload;

    /**
     * @param int    $status_code
     * @param string $reason_phrase
     * @param mixed  $payload
     */
    public function __construct($status_code, $reason_phrase = '', $payload = null)
    {
        $this->status_code = (int) $status_code;
        $this->reason_phrase = (string) $reason_phrase;
        $this->payload = $payload;
    }

    public function getStatusCode()
    {
        return $this->status_code;
    }

    public function getReasonPhrase()
    {
        return $this->reason_phrase;
    }

    public function getPayload()
    {
        return $this->payload;
    }
}
