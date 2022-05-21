<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\QrGenerator;

class DummyQrGenerator implements QrGeneratorInterface
{
    public function generate(string $data): QrGeneratorInterface
    {
        return $this;
    }

    public function writeString(): string
    {
        return '';
    }

    public function writeDataUri(): string
    {
        return '';
    }

    public function writeFile(string $path): void
    {
    }
}
