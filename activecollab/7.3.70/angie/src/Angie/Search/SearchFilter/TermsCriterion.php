<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchFilter;

use InvalidArgumentException;

final class TermsCriterion extends SearchCriterion implements SearchCriterionInterface
{
    /**
     * @param string $field
     * @param array  $value
     */
    public function __construct($field, $value)
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException('Value must be an array.');
        }

        parent::__construct($field, $value);
    }

    public function serialize()
    {
        $result = [];

        foreach ($this->getValue() as $v) {
            $result[] = $this->serializeValue($v);
        }

        return $result;
    }
}
