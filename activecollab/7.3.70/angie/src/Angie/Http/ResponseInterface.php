<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Http;

use Psr\Http\Message\ResponseInterface as BaseResponseInterface;

interface ResponseInterface extends BaseResponseInterface
{
    const OK = 200;
    const CREATED = 201;
    const ACCEPTED = 202;
    const NO_CONTENT = 204;
    const MOVED_PERMANENTLY = 301;
    const MOVED_TEMPORARILY = 302;
    const NOT_MODIFIED = 304;
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const INVALID_PROPERTIES = 400;
    const NOT_ACCEPTABLE = 406;
    const CONFLICT = 409;
    const GONE = 410;
    const OPERATION_FAILED = 500;
    const UNAVAILABLE = 503;
}
