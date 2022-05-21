<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Middleware;

use ActiveCollab\Authentication\AuthenticationResult\Transport\Authorization\AuthorizationTransportInterface;
use ActiveCollab\Authentication\AuthenticationResult\Transport\CleanUp\CleanUpTransportInterface;
use ActiveCollab\Authentication\AuthenticationResult\Transport\Deauthentication\DeauthenticationTransportInterface;
use ActiveCollab\Authentication\Session\SessionInterface;
use ActiveCollab\Cookies\CookiesInterface;
use Angie\Middleware\Base\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use User;

/**
 * @package Angie\Middleware
 */
class LanguageCookieMiddleware extends Middleware
{
    /**
     * @var CookiesInterface
     */
    private $cookies;

    /**
     * @var string
     */
    private $language_cookie_name;

    /**
     * @var string
     */
    private $default_locale;

    /**
     * EtagMiddleware constructor.
     *
     * @param string $language_cookie_name
     * @param string $default_locale
     */
    public function __construct(CookiesInterface $cookies, $language_cookie_name, $default_locale, LoggerInterface $logger = null)
    {
        parent::__construct($logger);

        $this->cookies = $cookies;
        $this->language_cookie_name = $language_cookie_name;
        $this->default_locale = $default_locale;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $action_result = $request->getAttribute(self::ACTION_RESULT_ATTRIBUTE);

        // User is logging in
        if ($action_result instanceof AuthorizationTransportInterface) {
            $authenticated_user = $action_result->getAuthenticatedUser();
            $authenticated_with = $action_result->getAuthenticatedWith();

            if ($authenticated_user instanceof User && $authenticated_with instanceof SessionInterface) {
                [$request, $response] = $this->setCookieValue($request, $response, $authenticated_user->getLanguage()->getLocale(), $authenticated_with->getSessionTtl());
            }

        // User is logging out
        } elseif ($action_result instanceof DeauthenticationTransportInterface || $action_result instanceof CleanUpTransportInterface) {
            [$request, $response] = $this->removeCookieValue($request, $response);

        // Just a regular visit
        } else {
            $authenticated_user = $request->getAttribute('authenticated_user');
            $authenticated_with = $request->getAttribute('authenticated_with');

            if ($authenticated_user instanceof User && $authenticated_with instanceof SessionInterface) {
                [$request, $response] = $this->setCookieValue($request, $response, $authenticated_user->getLanguage()->getLocale(), $authenticated_with->getSessionTtl());
            }
        }

        if ($next) {
            $response = $next($request, $response);
        }

        return $response;
    }

    /**
     * Set (or extend) language cookie.
     *
     * @param  string $language_code
     * @param  int    $cookie_ttl
     * @return array
     */
    private function setCookieValue(ServerRequestInterface $request, ResponseInterface $response, $language_code, $cookie_ttl)
    {
        return $this->cookies->set($request, $response, $this->language_cookie_name, $language_code, [
            'ttl' => $cookie_ttl,
            'http_only' => false,
            'encrypt' => false,
        ]);
    }

    /**
     * Remove language cookie, if found.
     *
     * @return array
     */
    private function removeCookieValue(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($this->cookies->exists($request, $this->language_cookie_name)) {
            return $this->cookies->remove($request, $response, $this->language_cookie_name);
        }

        return [$request, $response];
    }
}
