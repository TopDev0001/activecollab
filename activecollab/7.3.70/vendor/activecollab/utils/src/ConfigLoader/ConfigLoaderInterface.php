<?php

/*
 * This file is part of the Active Collab Utils project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ConfigLoader;

interface ConfigLoaderInterface
{
    public function isLoaded();

    public function load(): ConfigLoaderInterface;

    public function hasValue(string $option_name): bool;
    public function getValue(string $option_name, $default = null);

    public function requirePresence(...$config_options): ConfigLoaderInterface;
    public function requireValue(...$config_options): ConfigLoaderInterface;
    public function requirePresenceWhen($option, $has_value, ...$require_config_options): ConfigLoaderInterface;
    public function requireValueWhen($option, $has_value, ...$require_config_options): ConfigLoaderInterface;
}
