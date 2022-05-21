<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Middleware\Base;

use Angie\Http\Encoder\EncoderInterface;
use Psr\Log\LoggerInterface;

/**
 * @package Angie\Middleware\Base
 */
abstract class EncoderMiddleware extends Middleware
{
    /**
     * @var EncoderInterface
     */
    private $encoder;

    public function __construct(EncoderInterface $encoder, LoggerInterface $logger = null)
    {
        parent::__construct($logger);

        $this->encoder = $encoder;
    }

    /**
     * @return EncoderInterface
     */
    protected function getEncoder()
    {
        return $this->encoder;
    }
}
