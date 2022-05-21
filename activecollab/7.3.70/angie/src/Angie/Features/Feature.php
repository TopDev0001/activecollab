<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Features;

use ActiveCollab\EventsDispatcher\EventsDispatcherInterface;
use ActiveCollab\Module\System\Events\FeatureEvents\FeatureActivatedEvent;
use ActiveCollab\Module\System\Events\FeatureEvents\FeatureDeactivatedEvent;
use ConfigOptionDnxError;
use ConfigOptions;
use JsonSerializable;

abstract class Feature implements FeatureInterface, JsonSerializable
{
    private EventsDispatcherInterface $dispatcher;

    public function __construct(EventsDispatcherInterface $dispatcher) {
        $this->dispatcher = $dispatcher;
    }

    public function getDispatcher(): EventsDispatcherInterface
    {
        return $this->dispatcher;
    }

    public function activate(): bool
    {
        ConfigOptions::setValue($this->getIsEnabledFlag(), true, true);
        ConfigOptions::setValue($this->getIsEnabledLockFlag(), false, true);

        $this->dispatcher->trigger(new FeatureActivatedEvent($this));

        return true;
    }

    public function deactivate(): bool
    {
        ConfigOptions::setValue($this->getIsEnabledFlag(), false, true);
        ConfigOptions::setValue($this->getIsEnabledLockFlag(), true, true);

        $this->dispatcher->trigger(new FeatureDeactivatedEvent($this));

        return true;
    }

    public function enable(): bool
    {
        ConfigOptions::setValue($this->getIsEnabledFlag(), true, true);

        return true;
    }

    public function disable(): bool
    {
        ConfigOptions::setValue($this->getIsEnabledFlag(), false, true);

        return true;
    }

    public function isEnabled(): bool
    {
        try {
            return (bool) ConfigOptions::getValue($this->getIsEnabledFlag());
        } catch (ConfigOptionDnxError $e) {
            return false;
        }
    }

    public function isLocked(): bool
    {
        try {
            return (bool) ConfigOptions::getValue($this->getIsEnabledLockFlag());
        } catch (ConfigOptionDnxError $e) {
            return true;
        }
    }

    abstract public function getIsEnabledFlag(): string;
    abstract public function getIsEnabledLockFlag(): string;

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'verbose_name' => $this->getVerbose(),
            'is_enabled' => $this->isEnabled(),
            'is_locked' => $this->isLocked(),
        ];
    }
}
