<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\Compilers;

interface CompiledFileInterface
{
    public function writeLine(string $line = ''): void;
    public function save(string $file_path): bool;
}
