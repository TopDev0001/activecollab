<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchFilter;

use InvalidArgumentException;

final class TermCriterion extends SearchCriterion implements SearchCriterionInterface
{
    /**
     * @param string          $field
     * @param int|string|bool $value
     */
    public function __construct($field, $value)
    {
        if (is_array($value)) {
            throw new InvalidArgumentException("Value can't be an array. Use Terms instead.");
        }

        parent::__construct($field, $value);
    }

    public function serialize()
    {
        return [
            'value' => $this->serializeValue($this->getValue()),
        ];
    }
}
