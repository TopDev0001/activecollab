<?php

/*
 * This file is part of the Active Collab Utils project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ConfigLoader;

use Dotenv\Dotenv;
use LogicException;

class DotEnvConfigLoader extends ConfigLoader
{
    private $dotenv;

    public function __construct(Dotenv $dotenv)
    {
        $this->dotenv = $dotenv;
    }

    protected function onLoad()
    {
        parent::onLoad();

        $this->dotenv->load();
    }

    public function hasValue(string $option_name): bool
    {
        if (!$this->canCheckValuePresence()) {
            throw new LogicException('Options not loaded.');
        }

        return getenv($this->normalizeOptionName($option_name)) !== false;
    }

    public function getValue(string $option_name, $default = null)
    {
        if (!$this->canGetValue()) {
            throw new LogicException('Options not loaded.');
        }

        $value = getenv($this->normalizeOptionName($option_name));

        return $value === false ? $default : $value;
    }
}
