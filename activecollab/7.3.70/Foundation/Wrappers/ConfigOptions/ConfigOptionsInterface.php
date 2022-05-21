<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Wrappers\ConfigOptions;

interface ConfigOptionsInterface
{
    public function exists(string $name): bool;
    public function getValue(string $name, $use_cache = true);
    public function setValue(string $name, $value = null);
}
