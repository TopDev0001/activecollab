<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\Bounce;

use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\MiddlewareResult;

abstract class Bounce extends MiddlewareResult implements BounceInterface
{
}
