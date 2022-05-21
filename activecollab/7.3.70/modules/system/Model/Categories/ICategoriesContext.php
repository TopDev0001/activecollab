<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Model\Categories;

use Category;

interface ICategoriesContext
{
    /**
     * Return categories, optionally filtered by type.
     *
     * @return Category[]
     */
    public function getCategories(string $type = null): ?iterable;

    /**
     * @return int
     */
    public function getId();
}
