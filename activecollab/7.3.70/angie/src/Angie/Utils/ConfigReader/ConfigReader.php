<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Utils\ConfigReader;

use ConfigOptions;

class ConfigReader implements ConfigReaderInterface
{
    public function getValue(string $option_name)
    {
        return ConfigOptions::getValue($option_name);
    }
}
