<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchFilter;

use DateTimeValue;
use DateValue;
use LogicException;

final class BoolFilter implements BoolFilterInterface
{
    /**
     * @var SearchCriterionInterface[]
     */
    private $must = [];

    public function getMust()
    {
        return $this->must;
    }

    public function getMustCriterion($for_field)
    {
        return !empty($this->must[$for_field]) ? $this->must[$for_field] : null;
    }

    public function must(SearchCriterionInterface $criterion)
    {
        if (!empty($this->must[$criterion->getField()])) {
            throw new LogicException(sprintf('Criterion for %s field already set.', $criterion->getField()));
        }

        $this->must[$criterion->getField()] = $criterion;

        return $this;
    }

    public function mustReplace(SearchCriterionInterface $criterion)
    {
        $this->must[$criterion->getField()] = $criterion;

        return $this;
    }

    public function serialize()
    {
        $result = [];

        $must = $this->serializeCriterions($this->getMust());

        if (!empty($must)) {
            $result['must'] = $must;
        }

        return $result;
    }

    private function serializeCriterions(array $criterions)
    {
        $result = [];

        foreach ($criterions as $criterion) {
            $criterion_value = $criterion->getValue();

            if ($criterion->getOperator() === SearchCriterionInterface::FILTER_BETWEEN) {
                if ($this->isValidRangeValue($criterion_value)) {
                    $this->serializeRange($criterion->getField(), $criterion_value[0], $criterion_value[1], $result);
                } else {
                    throw new \RuntimeException('Invalid range value.');
                }
            } else {
                if (is_array($criterion_value)) {
                    $this->serializeTerms($criterion->getField(), $criterion_value, $result);
                } else {
                    $this->serializeTerm($criterion->getField(), $criterion_value, $result);
                }
            }
        }

        return $result;
    }

    private function serializeTerm($field, $value, array &$result)
    {
        if (empty($result['term'])) {
            $result['term'] = [];
        }

        $result['term'][$field] = [
            'value' => $this->serializeValue($value),
        ];
    }

    private function serializeTerms($field, array $values, array &$result)
    {
        if (empty($result['terms'])) {
            $result['terms'] = [];
        }

        $serialized_values = [];

        foreach ($values as $value) {
            $serialized_values[] = $this->serializeValue($value);
        }

        $result['terms'][$field] = $serialized_values;
    }

    private function serializeRange($field, $from, $to, array &$result)
    {
        if (empty($result['range'])) {
            $result['range'] = [];
        }

        if ($from instanceof DateTimeValue && $to instanceof DateTimeValue) {
            $result['range'][$field] = [
                'gte' => $this->serializeValue($from),
                'lte' => $this->serializeValue($to),
                'format' => 'date_hour_minute_second',
            ];
        } elseif ($from instanceof DateValue && $to instanceof DateValue) {
            $result['range'][$field] = [
                'gte' => $this->serializeValue($from),
                'lte' => $this->serializeValue($to),
                'format' => 'date',
            ];
        } else {
            $result['range'][$field] = [
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
