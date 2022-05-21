<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\IncomingMail;

use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Middleware as IncomingMailMiddleware;

abstract class Middleware extends IncomingMailMiddleware
{
}
