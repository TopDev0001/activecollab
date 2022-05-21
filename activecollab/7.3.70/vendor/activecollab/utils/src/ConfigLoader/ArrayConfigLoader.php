<?php

/*
 * This file is part of the Active Collab Utils project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ConfigLoader;

use ActiveCollab\ConfigLoader\Exception\ValidationException;
use InvalidArgumentException;
use LogicException;

class ArrayConfigLoader extends ConfigLoader
{
    private $loaded_options = [];

    private $options_file_path;

    public function __construct($options_file_path)
    {
        if (!is_file($options_file_path)) {
            throw new InvalidArgumentException('Options file does not exist.');
        }

        $this->options_file_path = $options_file_path;
    }

    protected function onLoad()
    {
        parent::onLoad();

        $options = require $this->options_file_path;

        if (!is_array($options)) {
            $options = [];
        }

        $this->loaded_options = $options;
    }

    protected function onValidationFailed(ValidationException $e)
    {
        parent::onValidationFailed($e);

        $this->loaded_options = [];
    }

    public function hasValue($option_name): bool
    {
        if (!$this->canCheckValuePresence()) {
            throw new LogicException('Options not loaded.');
        }

        return array_key_exists($this->normalizeOptionName($option_name), $this->loaded_options);
    }

    public function getValue(string $option_name, $default = null)
    {
        if (!$this->canGetValue()) {
            throw new LogicException('Options not loaded.');
        }

        $option_name = $this->normalizeOptionName($option_name);

        if (array_key_exists($option_name, $this->loaded_options)) {
            return $this->loaded_options[$option_name];
        } else {
            return $default;
        }
    }
}
