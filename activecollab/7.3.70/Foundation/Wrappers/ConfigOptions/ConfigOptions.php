<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Wrappers\ConfigOptions;

use ConfigOptions as WrappedConfigOptions;

class ConfigOptions implements ConfigOptionsInterface
{
    public function exists(string $name): bool
    {
        return (bool) WrappedConfigOptions::exists($name);
    }

    public function getValue(string $name, $use_cache = true)
    {
        return WrappedConfigOptions::getValue($name, $use_cache);
    }

    public function setValue(string $name, $value = null)
    {
        return WrappedConfigOptions::setValue($name, $value);
    }
}
