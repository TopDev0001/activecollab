<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Command\Chat;

use Angie\Command\Command;

abstract class ChatCommand extends Command
{
    protected function getCommandNamePrefix(): string
    {
        return parent::getCommandNamePrefix() . 'chat:';
    }
}
