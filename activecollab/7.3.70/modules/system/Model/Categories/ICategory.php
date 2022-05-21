<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Model\Categories;

use Category;

interface ICategory
{
    public function getCategory(): ?Category;
    public function setCategory(?Category $category, bool $save = false): ?Category;

    /**
     * Return category ID.
     *
     * @return int
     */
    public function getCategoryId();

    /**
     * Set category ID.
     *
     * @param int $value
     * @return int
     */
    public function setCategoryId($value);
}
