<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;

abstract class Category extends BaseCategory implements RoutingContextInterface, IHistory
{
    use IHistoryImplementation;

    /**
     * Return type name.
     *
     * By default, this function will return 'category', but it can be changed
     * in 'group', 'collection' etc - whatever fits the needs of the specific
     * situation where specific category type is used
     *
     * @return string
     */
    public function getTypeName()
    {
        return 'category';
    }

    /**
     * Set attributes.
     *
     * @param array $attributes
     */
    public function setAttributes($attributes)
    {
        if (is_array($attributes) && isset($attributes['name'])) {
            $attributes['name'] = trim($attributes['name']);
        }

        parent::setAttributes($attributes);
    }

    public function getRoutingContext(): string
    {
        return 'category';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'category_id' => $this->getId(),
        ];
    }

    /**
     * Validate model before save.
     */
    public function validate(ValidationErrors & $errors)
    {
        if ($this->validatepresenceOf('name')) {
            if ($this->getParentType() && $this->getParentId()) {
                $validate_uniqueness = $this->validateUniquenessOf('parent_type', 'parent_id', 'type', 'name');
            } else {
                $validate_uniqueness = $this->validateUniquenessOf('type', 'name');
            }

            if (!$validate_uniqueness) {
                $errors->addError('Name needs to be unique', 'name');
            }
        } else {
            $errors->addError('Name is required', 'name');
        }
    }
}
