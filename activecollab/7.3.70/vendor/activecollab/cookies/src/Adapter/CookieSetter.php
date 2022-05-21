<?php

/*
 * This file is part of the Active Collab Cookies project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Cookies\Adapter;

use ActiveCollab\CurrentTimestamp\CurrentTimestamp;
use ActiveCollab\CurrentTimestamp\CurrentTimestampInterface;
use Dflydev\FigCookies\Cookie;
use Dflydev\FigCookies\Cookies;
use Dflydev\FigCookies\SetCookie;
use Dflydev\FigCookies\SetCookies;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CookieSetter implements CookieSetterInterface
{
    protected string $name;

    /**
     * @var mixed
     */
    private $value;

    private CurrentTimestampInterface $currentTimestamp;

    private string $domain;
    private string $path;
    private int $timeToLive;
    private int $expires;
    private bool $secure;
    private bool $httpOnly;


    public function __construct(
        string $name,
        $value,
        array $settings,
        CurrentTimestampInterface $currentTimestamp
    )
    {
        $this->name = $name;
        $this->value = $value;
        $this->currentTimestamp = $currentTimestamp ?? new CurrentTimestamp();

        $this->domain = isset($settings['domain']) ? (string) $settings['domain'] : '';
        $this->path = isset($settings['path']) ? (string) $settings['path'] : '/';
        $this->timeToLive = isset($settings['ttl']) ? $settings['ttl'] : 0;
        $this->expires = isset($settings['expires'])
            ? (int) $settings['expires']
            : $this->currentTimestamp->getCurrentTimestamp() + $this->timeToLive;
        $this->secure = !empty($settings['secure']);
        $this->httpOnly = !empty($settings['http_only']);
    }

    public function applyToRequest(RequestInterface $request): RequestInterface
    {
        $cookies = Cookies::fromRequest($request);

        if ($cookies->has($this->name)) {
            $cookie = $cookies->get($this->name)->withValue($this->value);
        } else {
            $cookie = Cookie::create($this->name, $this->value);
        }

        return $cookies
            ->with($cookie)
            ->renderIntoCookieHeader($request);
    }

    public function applyToResponse(ResponseInterface $response): ResponseInterface
    {
        $setCookies = SetCookies::fromResponse($response);

        if ($setCookies->has($this->name)) {
            $set_cookie = $setCookies->get($this->name)->withValue($this->value);
        } else {
            $set_cookie = SetCookie::create($this->name, $this->value);
        }

        $set_cookie = $set_cookie
            ->withDomain($this->domain)
            ->withPath($this->path)
            ->withSecure($this->secure)
            ->withExpires(date(DATE_COOKIE, $this->expires))
            ->withHttpOnly($this->httpOnly);

        return $setCookies->with($set_cookie)->renderIntoSetCookieHeader($response);
    }
}
