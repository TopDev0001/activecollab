<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

abstract class BaseExpenseCategories extends DataManager
{
    /**
     * Return name of this model.
     */
    public static function getModelName(bool $underscore = false): string
    {
        return $underscore ? 'expense_categories' : 'ExpenseCategories';
    }

    /**
     * Return name of the table where system will persist model instances.
     */
    public static function getTableName(): string
    {
        return 'expense_categories';
    }

    /**
     * All table fields.
     */
    private static array $fields = [
        'id',
        'name',
        'is_default',
        'is_archived',
    ];

    /**
     * Return a list of model fields.
     */
    public static function getFields(): array
    {
        return self::$fields;
    }

    /**
     * Return class name of a single instance.
     */
    public static function getInstanceClassName(): string
    {
        return ExpenseCategory::class;
    }

    /**
     * Return whether instance class name should be loaded from a field, or based on table name.
     */
    public static function getInstanceClassNameFrom(): int
    {
        return DataManager::CLASS_NAME_FROM_TABLE;
    }

    /**
     * Return name of the field from which we will read instance class.
     */
    public static function getInstanceClassNameFromField(): string
    {
        return '';
    }

    /**
     * Return name of this model.
     */
    public static function getDefaultOrderBy(): string
    {
        return 'name';
    }

    /**
     * @return ExpenseCategory[]|DbResult|null
     */
    public static function find($arguments = null): ?iterable
    {
        return parent::find($arguments);
    }

    public static function findOne($arguments = null): ?ExpenseCategory
    {
        return parent::findOne($arguments);
    }

    /**
     * @return ExpenseCategory[]|DbResult|null
     */
    public static function findBySQL(...$arguments): ?iterable
    {
        return parent::findBySQL(...$arguments);
    }

    /**
     * @return ExpenseCategory[]|DbResult|null
     */
    public static function findBy($field, $value = null): ?iterable
    {
        return parent::findBy($field, $value);
    }

    public static function findOneBy($field, $value = null): ?ExpenseCategory
    {
        return parent::findOneBy($field, $value);
    }

    /**
     * @return ExpenseCategory[]|DbResult|null
     */
    public static function findByIds(array $ids, bool $ordered_by_ids = false): ?iterable
    {
        return parent::findByIds($ids, $ordered_by_ids);
    }

    /**
     * @return ExpenseCategory[]
     */
    public static function createMany(
        array $records,
        bool $save = true,
        bool $announce = true
    ): iterable
    {
        return parent::createMany($records, $save, $announce);
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): ExpenseCategory
    {
        return parent::create($attributes, $save, $announce);
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): ExpenseCategory
    {
        return parent::update($instance, $attributes, $save);
    }

    /**
     * @return ExpenseCategory|bool
     */
    public static function scrap(
        DataObject &$instance,
        bool $force_delete = false
    )
    {
        return parent::scrap($instance, $force_delete);
    }
}