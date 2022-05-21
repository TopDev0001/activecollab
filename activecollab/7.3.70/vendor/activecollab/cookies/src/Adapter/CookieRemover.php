<?php

/*
 * This file is part of the Active Collab Cookies project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Cookies\Adapter;

use ActiveCollab\CurrentTimestamp\CurrentTimestampInterface;
use Dflydev\FigCookies\Cookies;
use Psr\Http\Message\RequestInterface;

class CookieRemover extends CookieSetter implements CookieRemoverInterface
{
    public function __construct(
        string $name,
        array $settings,
        CurrentTimestampInterface $currentTimestamp
    )
    {
        parent::__construct(
            $name,
            '',
            array_merge(
                $settings,
                [
                    'ttl' => -172800,
                ]
            ),
            $currentTimestamp
        );
    }

    public function applyToRequest(RequestInterface $request): RequestInterface
    {
        return Cookies::fromRequest($request)
            ->without($this->name)
            ->renderIntoCookieHeader($request);
    }
}
