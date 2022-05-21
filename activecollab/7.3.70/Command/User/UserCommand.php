<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command\User;

use Angie\Command\Command;
use InvalidArgumentException;
use Users;

abstract class UserCommand extends Command
{
    protected function getCommandNamePrefix(): string
    {
        return parent::getCommandNamePrefix() . 'user:';
    }

    protected function getValidUserType(string $type): string
    {
        if (!in_array($type, Users::getAvailableUserClasses())) {
            throw new InvalidArgumentException(sprintf('Class %s is not a valid user type', $type));
        }

        return $type;
    }
}
