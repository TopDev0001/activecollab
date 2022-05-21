<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchFilter;

use DateTimeValue;
use DateValue;

abstract class SearchCriterion implements SearchCriterionInterface
{
    private $field;
    private $value;

    /**
     * @param string $field
     * @param mixed  $value
     */
    public function __construct($field, $value)
    {
        $this->field = $field;
        $this->value = $value;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getValue()
    {
        return $this->value;
    }

    protected function serializeValue($value)
    {
        return $value instanceof DateTimeValue || $value instanceof DateValue
            ? $value->toMySQL()
            : $value;
    }
}
