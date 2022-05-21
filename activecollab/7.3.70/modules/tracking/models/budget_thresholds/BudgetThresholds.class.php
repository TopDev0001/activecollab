<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class BudgetThresholds extends BaseBudgetThresholds
{
    /**
     * @param  string          $collection_name
     * @param  null            $user
     * @return ModelCollection
     */
    public static function prepareCollection($collection_name, $user = null)
    {
        $collection = parent::prepareCollection($collection_name, $user);

        if (str_starts_with($collection_name, 'budget_thresholds_for_')) {
            $bits = explode('_', $collection_name);
            $project_id = (int) array_pop($bits);
            $collection->setConditions('project_id = ?', $project_id);
        }

        return $collection;
    }
}
