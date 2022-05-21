<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Http\Encoder;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Angie\Http
 */
interface EncoderInterface
{
    /**
     * Encode value to $response.
     *
     * @param  mixed $value
     * @return array
     */
    public function encode($value, ServerRequestInterface $request, ResponseInterface $response);

    /**
     * Return true if Encoder is running in debug or development mode, where more info about data conditions may be exposed to the user.
     *
     * @return bool
     */
    public function isDebugOrDevelopment();
}
