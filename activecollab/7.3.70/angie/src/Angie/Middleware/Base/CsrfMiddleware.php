<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Middleware\Base;

use ActiveCollab\Cookies\CookiesInterface;
use Angie\Http\Encoder\EncoderInterface;
use Psr\Log\LoggerInterface;

/**
 * @package Angie\Middleware\Base
 */
abstract class CsrfMiddleware extends EncoderMiddleware
{
    /**
     * @var CookiesInterface
     */
    protected $cookies;

    /**
     * @var string
     */
    private $csrf_validator_cookie_name;

    /**
     * CsrfMiddleware constructor.
     *
     * @param string $csrf_validator_cookie_name
     */
    public function __construct(CookiesInterface $cookies, $csrf_validator_cookie_name, EncoderInterface $encoder, LoggerInterface $logger = null)
    {
        parent::__construct($encoder, $logger);

        $this->cookies = $cookies;
        $this->csrf_validator_cookie_name = $csrf_validator_cookie_name;
    }

    /**
     * Return CSRF validator cookie name.
     *
     * @return string
     */
    protected function getCsrfValidatorCookieName()
    {
        return $this->csrf_validator_cookie_name;
    }

    /**
     * Return CSRF validator header name.
     *
     * @return string
     */
    protected function getCsrfValidatorHeaderName()
    {
        return 'X-Angie-CsrfValidator';
    }
}
