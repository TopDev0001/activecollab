<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Model\Categories;

use Categories;
use Category;
use DB;

trait ICategoriesContextImplementation
{
    /**
     * Return categories, optionally filtered by type.
     *
     * @return Category[]
     */
    public function getCategories(string $type = null): ?iterable
    {
        $conditions = [
            Categories::parentToCondition($this),
        ];

        if ($type) {
            $conditions[] = DB::prepare('(`type` = ?)', $type);
        }

        return Categories::find(
            [
                'conditions' => implode(' AND ', $conditions),
            ]
        );
    }

    /**
     * Return ID of this instance.
     *
     * @return int
     */
    abstract public function getId();
}
