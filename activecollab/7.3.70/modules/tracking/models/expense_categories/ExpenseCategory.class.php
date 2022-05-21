<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * ExpenseCategory class.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage models
 */
class ExpenseCategory extends BaseExpenseCategory
{
    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'is_default' => $this->getIsDefault(),
                'is_archived' => $this->getIsArchived(),
            ]
        );
    }

    // ---------------------------------------------------
    //  Interface implementations
    // ---------------------------------------------------

    public function getRoutingContext(): string
    {
        return 'expense_category';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'expense_category_id' => $this->getId(),
        ];
    }

    /**
     * Return true if this expense category is used for estimate.
     *
     * @return bool
     */
    public function isUsed()
    {
        return (bool) Expenses::countByCategory($this);
    }

    public function canView(User $user): bool
    {
        return $user->isOwner();
    }

    public function canEdit(User $user): bool
    {
        return $user->isOwner();
    }

    public function canArchive(User $user): bool
    {
        return $user->isOwner() && !$this->getIsDefault();
    }

    public function canDelete(User $user): bool
    {
        return $user->isOwner() && !($this->getIsDefault() || ExpenseCategories::count() <= 1);
    }

    // ---------------------------------------------------
    //  System
    // ---------------------------------------------------

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors & $errors)
    {
        if ($this->validatePresenceOf('name')) {
            $this->validateUniquenessOf('name') or $errors->fieldValueNeedsToBeUnique('name');
        } else {
            $errors->fieldValueIsRequired('name');
        }
    }
}
