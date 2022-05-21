<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchFilter;

use DateTimeValue;
use DateValue;
use InvalidArgumentException;

final class RangeCriterion extends SearchCriterion
{
    /**
     * @param string $field
     * @param array  $value
     */
    public function __construct($field, $value)
    {
        if (!$this->isValidRangeValue($value)) {
            throw new InvalidArgumentException('Value needs to be an array of two values.');
        }

        parent::__construct($field, $value);
    }

    public function serialize()
    {
        [$from, $to] = $this->getValue();

        if ($from instanceof DateTimeValue && $to instanceof DateTimeValue) {
            return [
                'gte' => $this->serializeValue($from),
                'lte' => $this->serializeValue($to),
                'format' => 'date_hour_minute_second',
            ];
        } elseif ($from instanceof DateValue && $to instanceof DateValue) {
            return [
                'gte' => $this->serializeValue($from),
                'lte' => $this->serializeValue($to),
                'format' => 'date',
            ];
        } else {
            return [
                'gte' => $this->serializeValue($from),
                'lte' => $this->serializeValue($to),
            ];
        }
    }

    private function isValidRangeValue($value)
    {
        return is_array($value)
            && count($value) === 2
            && array_key_exists(0, $value)
            && array_key_exists(1, $value);
    }
}
