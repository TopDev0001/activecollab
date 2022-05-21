<?php

/*
 * This file is part of the Active Collab Utils project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ConfigLoader;

use ActiveCollab\ConfigLoader\Exception\ValidationException;
use LogicException;

abstract class ConfigLoader implements ConfigLoaderInterface
{
    private $is_loading = false;

    private $is_loaded = false;

    private $required_presence = [];

    private $required_value = [];

    private $required_presence_when = [];

    private $required_value_when = [];

    public function isLoading()
    {
        return $this->is_loading;
    }

    protected function &setIsLoading($value = true)
    {
        $this->is_loading = (bool) $value;

        return $this;
    }

    public function isLoaded(): bool
    {
        return $this->is_loaded;
    }

    protected function &setIsLoaded($value = true)
    {
        $this->is_loaded = (bool) $value;

        return $this;
    }

    public function load(): ConfigLoaderInterface
    {
        if ($this->isLoaded()) {
            throw new LogicException('Options already loaded.');
        }

        $this->setIsLoading(true);
        $this->onLoad();

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->onValidationFailed($e);
            throw $e;
        } finally {
            $this->setIsLoading(false);
        }

        $this->setIsLoaded(true);

        return $this;
    }

    protected function onLoad()
    {
        if (!$this->isLoading()) {
            throw new LogicException('This method should be called only during loading.');
        }
    }

    protected function onValidationFailed(ValidationException $e)
    {
        if (!$this->isLoading()) {
            throw new LogicException('This method should be called only during loading.');
        }
    }

    protected function canCheckValuePresence()
    {
        return $this->isLoading() || $this->isLoaded();
    }

    protected function canGetValue()
    {
        return $this->isLoading() || $this->isLoaded();
    }

    public function requirePresence(...$config_options): ConfigLoaderInterface
    {
        if ($this->isLoaded()) {
            throw new LogicException('Options can be required only before they are loaded.');
        }

        $this->required_presence = array_unique(array_merge($this->required_presence, $config_options));

        return $this;
    }

    public function requireValue(...$config_options): ConfigLoaderInterface
    {
        if ($this->isLoaded()) {
            throw new LogicException('Option values can be required only before they are loaded.');
        }

        $this->required_value = array_unique(array_merge($this->required_value, $config_options));

        return $this;
    }

    public function requirePresenceWhen($option, $has_value, ...$require_config_options): ConfigLoaderInterface
    {
        if ($this->isLoaded()) {
            throw new LogicException('Options can be required only before they are loaded.');
        }

        $this->requireValue($option);

        $this->required_presence_when[] = [$option, $has_value, $require_config_options];

        return $this;
    }

    public function requireValueWhen($option, $has_value, ...$require_config_options): ConfigLoaderInterface
    {
        if ($this->isLoaded()) {
            throw new LogicException('Options can be required only before they are loaded.');
        }

        $this->requireValue($option);

        $this->required_value_when[] = [$option, $has_value, $require_config_options];

        return $this;
    }

    protected function validate(): ConfigLoaderInterface
    {
        $exception = new ValidationException();

        $exception = $this->validateRequiedPresence($this->required_presence, $exception);
        $exception = $this->validateRequiredValue($this->required_value, $exception);
        $exception = $this->validateConditionalRequiredPresence($this->required_presence_when, $exception);
        $exception = $this->validateConditionalRequiredValue($this->required_value_when, $exception);

        if ($exception->hasErrors()) {
            throw $exception;
        }

        return $this;
    }

    private function validateRequiedPresence($options, ValidationException $exception)
    {
        foreach ($options as $option_name) {
            if (!$this->hasValue($option_name)) {
                $exception->missing($option_name);
            }
        }

        return $exception;
    }

    private function validateRequiredValue($options, ValidationException $exception)
    {
        foreach ($options as $option_name) {
            if (!$this->hasValue($option_name)) {
                $exception->missing($option_name);
            } elseif (empty($this->getValue($option_name))) {
                $exception->missingValue($option_name);
            }
        }

        return $exception;
    }

    private function validateConditionalRequiredPresence($settings, ValidationException $exception)
    {
        foreach ($settings as $k) {
            list($when_option, $has_value, $require_presence_of_options) = $k;

            if ($this->getValue($when_option) == $has_value) {
                foreach ($require_presence_of_options as $option_name) {
                    if (!$this->hasValue($option_name)) {
                        $exception->missing($option_name);
                    }
                }
            }
        }

        return $exception;
    }

    private function validateConditionalRequiredValue($settings, ValidationException $exception)
    {
        foreach ($settings as $k) {
            list($when_option, $has_value, $require_presence_of_options) = $k;

            if ($this->getValue($when_option) == $has_value) {
                foreach ($require_presence_of_options as $option_name) {
                    if (!$this->hasValue($option_name)) {
                        $exception->missing($option_name);
                    } elseif (empty($this->getValue($option_name))) {
                        $exception->missingValue($option_name);
                    }
                }
            }
        }

        return $exception;
    }

    protected function normalizeOptionName($option_name)
    {
        return mb_strtoupper($option_name);
    }
}
