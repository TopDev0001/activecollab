<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\Bounce;

class UnknownSenderBounce extends Bounce
{
    public function getReason(): string
    {
        return lang("Your reply hasn't been posted as a comment. You need to have an account in ActiveCollab to be able to do this.");
    }
}
