<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;
use ActiveCollab\Module\Tracking\Events\DataObjectLifeCycleEvents\ExpenseEvents\ExpenseUpdatedEvent;
use Angie\Globalization;

class Expense extends BaseExpense implements RoutingContextInterface
{
    public function getHistoryFields(): array
    {
        return array_merge(
            parent::getHistoryFields(),
            [
                'category_id',
            ]
        );
    }

    public function isParentOptional(): bool
    {
        return false;
    }

    /**
     * Return name string.
     *
     * @param  bool   $detailed
     * @param  bool   $in_category
     * @return string
     */
    public function getName($detailed = false, $in_category = false)
    {
        if ($detailed) {
            $user = $this->getUser();
            $value = $this->getFormatedValue();

            if ($in_category) {
                return lang(':value in :category', ['value' => $value, 'category' => $this->getCategoryName()]);
            } else {
                if ($user instanceof IUser) {
                    return lang(':value by :name', ['value' => $value, 'name' => $user->getDisplayName(true)]);
                } else {
                    return $value;
                }
            }
        } else {
            return Globalization::formatMoney($this->getValue(), $this->getCurrency(), null, true);
        }
    }

    /**
     * Return value formated with currency.
     *
     * @return float
     */
    public function getFormatedValue()
    {
        return Globalization::formatMoney($this->getValue(), $this->getCurrency());
    }

    /**
     * Return Currency.
     *
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->getProject() instanceof Project && $this->getProject()->getCurrency() instanceof Currency ? $this->getProject()->getCurrency() : null;
    }

    /**
     * Return expense category name.
     *
     * @return string
     */
    public function getCategoryName()
    {
        return ExpenseCategories::getNameById($this->getCategoryId());
    }

    /**
     * Return expense category.
     *
     * @return ExpenseCategory
     */
    public function getCategory()
    {
        return DataObjectPool::get('ExpenseCategory', $this->getCategoryId());
    }

    /**
     * Set expense category.
     *
     * @return ExpenseCategory
     */
    public function setCategory(ExpenseCategory $category)
    {
        $this->setCategoryId($category->getId());

        return $category;
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['category_id'] = $this->getCategoryId();
        $result['currency_id'] = $this->getProject() instanceof Project && $this->getProject()->getCurrencyId()
            ? $this->getProject()->getCurrencyId()
            : Currencies::getDefaultId();

        return $result;
    }

    public function getRoutingContext(): string
    {
        return 'expense';
    }

    public function getRoutingContextParams(): array
    {
        $parent = $this->getParent();

        if ($parent instanceof Task) {
            $project = $parent->getProject();
        } else {
            $project = $parent;
        }

        return [
            'project_id' => $project->getId(),
            'expense_id' => $this->getId(),
        ];
    }

    public function canDelete(User $user): bool
    {
        return $this->canEdit($user);
    }

    public function moveToTrash(User $by = null, $bulk = false)
    {
        parent::moveToTrash($by, $bulk);

        AngieApplication::eventsDispatcher()->trigger(new ExpenseUpdatedEvent($this));
    }

    public function restoreFromTrash($bulk = false)
    {
        parent::restoreFromTrash($bulk);

        AngieApplication::eventsDispatcher()->trigger(new ExpenseUpdatedEvent($this));
    }
}
