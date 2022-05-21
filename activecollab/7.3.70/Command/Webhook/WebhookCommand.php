<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Command\Webhook;

use Angie\Command\Command;

abstract class WebhookCommand extends Command
{
    protected function getCommandNamePrefix(): string
    {
        return parent::getCommandNamePrefix() . 'webhook:';
    }
}
