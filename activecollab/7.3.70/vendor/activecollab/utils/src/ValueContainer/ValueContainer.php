<?php

/*
 * This file is part of the Active Collab Utils project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ValueContainer;

class ValueContainer implements ValueContainerInterface, WriteableValueContainerInterface
{
    private bool $value_is_set = false;
    private $value;

    public function hasValue(): bool
    {
        return $this->value_is_set;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value): ValueContainerInterface
    {
        $this->value = $value;

        return $this;
    }

    public function removeValue(): ValueContainerInterface
    {
        $this->value = null;
        $this->value_is_set = false;

        return $this;
    }
}
