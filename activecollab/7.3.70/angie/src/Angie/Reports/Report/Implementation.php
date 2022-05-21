<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Reports\Report;

use Angie\Reports\Report;
use IAdditionalProperties;
use InvalidParamError;
use NotImplementedError;

trait Implementation
{
    public function canBeGroupedBy(): array
    {
        return [];
    }

    public function getGroupingMaxLevel(): int
    {
        return 1;
    }

    public function isGrouped(): bool
    {
        $group_by = $this->getGroupBy();

        return array_shift($group_by) != Report::DONT_GROUP;
    }

    /**
     * Return array of properties that this report should be grouped by.
     *
     * @return array
     */
    public function getGroupBy()
    {
        if ($this instanceof IAdditionalProperties) {
            return (array) $this->getAdditionalProperty('group_by', [Report::DONT_GROUP]);
        } else {
            throw new NotImplementedError(__METHOD__);
        }
    }

    /**
     * Set group by.
     *
     * @return array
     */
    public function setGroupBy()
    {
        if (!$this instanceof IAdditionalProperties) {
            throw new NotImplementedError(__METHOD__);
        }

        $args_num = func_num_args();

        if ($args_num === 1) {
            $arg_value = func_get_arg(0);

            if (is_array($arg_value)) {
                $group_by = $arg_value;
            } elseif (strpos($arg_value, ',') !== false) {
                $group_by = explode(',', $arg_value);
            } elseif ($arg_value) {
                $group_by = [$arg_value];
            } else {
                $group_by = [
                    Report::DONT_GROUP,
                ];
            }
        } elseif ($args_num > 1) {
            $group_by = func_get_args();
        } else {
            $group_by = [
                Report::DONT_GROUP,
            ];
        }

        $group_by = array_unique($group_by);

        if (count($group_by) > $this->getGroupingMaxLevel()) {
            throw new InvalidParamError(
                'group_by',
                $group_by,
                sprintf('Max levels of grouping is %d', $this->getGroupingMaxLevel())
            );
        }

        return $this->setAdditionalProperty('group_by', $group_by);
    }

    /**
     * Reset group by settings.
     */
    public function ungroup()
    {
        $this->setGroupBy([Report::DONT_GROUP]);
    }
}
