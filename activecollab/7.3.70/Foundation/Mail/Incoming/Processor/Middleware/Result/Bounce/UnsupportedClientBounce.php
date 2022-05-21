<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\Bounce;

use LogicException;

class UnsupportedClientBounce extends Bounce implements UnsupportedClientBounceInterface
{
    private string $unsupported_client;

    public function __construct(string $unsupported_client)
    {
        if (!in_array($unsupported_client, self::UNSUPPORTED_CLIENTS)) {
            throw new LogicException(sprintf('Unknown email client %s', $unsupported_client));
        }

        $this->unsupported_client = $unsupported_client;
    }

    public function getReason(): string
    {
        if ($this->unsupported_client === self::ANDROID_MAIL) {
            return lang("The Android Email application isn't supported. Your reply hasn't been posted as a comment. Please use Gmail or a similar app instead.");
        }

        return lang("Your email client isn't supported. Your reply hasn't been posted as a comment. Please use a different email client.");
    }
}
