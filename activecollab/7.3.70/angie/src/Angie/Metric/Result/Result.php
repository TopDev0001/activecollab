<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Metric\Result;

use DateValue;
use InvalidArgumentException;

abstract class Result implements ResultInterface
{
    private $name;
    private $value;
    private $date;

    public function __construct(string $name, $value, DateValue $date)
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Metric name is required');
        }

        $this->name = $name;
        $this->value = $value;
        $this->date = $date;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getDate(): DateValue
    {
        return $this->date;
    }

    public function addTo(array &$stats): void
    {
        $stats[$this->getName()] = $this->getValue();
    }
}
