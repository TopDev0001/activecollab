<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\Bounce;

interface UnsupportedClientBounceInterface extends BounceInterface
{
    const ANDROID_MAIL = 'android';

    const UNSUPPORTED_CLIENTS = [
        self::ANDROID_MAIL,
    ];
}
