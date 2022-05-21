<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Model\Categories;

use Categories;
use Category;
use DataObjectPool;
use Language;

trait ICategoryImplementation
{
    protected function registerICategoryImplementation(): void
    {
        $this->registerEventHandler(
            'on_json_serialize',
            function (array & $result) {
                $result['category_id'] = $this->getCategoryId();
            }
        );

        $this->registerEventHandler(
            'on_describe_single',
            function (array & $result) {
                $result['category'] = $this->getCategory();
            }
        );

        $this->registerEventHandler(
            'on_history_field_renderers',
            function (array & $renderers) {
                $renderers['category_id'] = function ($old_value, $new_value, Language $language) {
                    $category_ids = [];

                    if ($old_value) {
                        $category_ids[] = $old_value;
                    }

                    if ($new_value) {
                        $category_ids[] = $new_value;
                    }

                    $names = Categories::getNamesByIds($category_ids);

                    if ($new_value) {
                        if ($old_value) {
                            return lang('Category changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $names[$old_value], 'new_value' => $names[$new_value]], true, $language);
                        } else {
                            return lang('Category set to <b>:new_value</b>', ['new_value' => $names[$new_value]], true, $language);
                        }
                    } elseif ($old_value) {
                        return lang('Category set to empty value', null, true, $language);
                    }
                };
            }
        );
    }

    /**
     * Return parent's category.
     */
    public function getCategory(): ?Category
    {
        return $this->getCategoryId()
            ? DataObjectPool::get(Category::class, $this->getCategoryId())
            : null;
    }

    public function setCategory(?Category $category, bool $save = false): ?Category
    {
        $this->setCategoryId($category ? $category->getId() : 0);

        if ($save) {
            $this->save();
        }

        return $category;
    }

    abstract protected function registerEventHandler(string $event, callable $handler): void;

    /**
     * Return category ID.
     *
     * @return int
     */
    abstract public function getCategoryId();

    /**
     * Set value of category_id field.
     *
     * @param  int $value
     * @return int
     */
    abstract public function setCategoryId($value);
}
