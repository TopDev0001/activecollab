<?php

/*
 * This file is part of the Active Collab Utils project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Encryptor;

interface EncryptorInterface
{
    public function encrypt($value): string;
    public function decrypt(string $value);
}
