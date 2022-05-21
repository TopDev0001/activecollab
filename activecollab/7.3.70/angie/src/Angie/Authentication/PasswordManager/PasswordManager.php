<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Authentication\PasswordManager;

use ActiveCollab\Authentication\Password\Manager\PasswordManager as BasePasswordManager;

class PasswordManager extends BasePasswordManager
{
    public function verify($password, $hash, $hashed_with)
    {
        if ($hashed_with && self::HASHED_WITH_PHP && password_verify($password, $hash)) {
            return true;
        }

        return parent::verify($password, $hash, $hashed_with);
    }

    public function hash($password, $hash_with = self::HASHED_WITH_PHP)
    {
        if ($hash_with === self::HASHED_WITH_PHP) {
            return password_hash((string) $password, PASSWORD_DEFAULT);
        }

        return parent::hash($password, $hash_with);
    }
}
