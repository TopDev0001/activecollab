<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Expense categories manager class.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage models
 */
class ExpenseCategories extends BaseExpenseCategories
{
    /**
     * @var array
     */
    private static $id_name_map = false;
    private static $full_id_name_map = false;

    public static function canAdd(User $user): bool
    {
        return $user instanceof User && $user->isOwner();
    }

    /**
     * Return job type name by job type ID.
     *
     * @param  int         $job_type_id
     * @return string|null
     */
    public static function getNameById($job_type_id)
    {
        $id_name_map = self::getIdNameMap();

        return array_var($id_name_map, $job_type_id);
    }

    /**
     * Return ID => name map.
     *
     * @param  bool  $include_archived
     * @param  bool  $use_cache
     * @return array
     */
    public static function getIdNameMap($include_archived = false, $use_cache = true)
    {
        if (!$use_cache || self::$id_name_map === false || self::$full_id_name_map === false) {
            self::$id_name_map = [];
            self::$full_id_name_map = [];

            if ($rows = DB::execute('SELECT id, name, is_archived FROM expense_categories ORDER BY name')) {
                foreach ($rows as $row) {
                    self::$full_id_name_map[$row['id']] = $row['name'];

                    if (!$row['is_archived']) {
                        self::$id_name_map[$row['id']] = $row['name'];
                    }
                }
            }
        }

        return $include_archived ? self::$full_id_name_map : self::$id_name_map;
    }

    // ---------------------------------------------------
    //  Default expense category
    // ---------------------------------------------------

    /**
     * Return default expense category.
     *
     * @return ExpenseCategory
     */
    public static function getDefault()
    {
        return DataObjectPool::get('ExpenseCategory', self::getDefaultId());
    }

    /**
     * Return default category ID.
     *
     * @return int
     */
    public static function getDefaultId()
    {
        return AngieApplication::cache()->get(['models', 'expense_categories', 'default_expense_category_id'], function () {
            return (int) DB::executeFirstCell('SELECT id FROM expense_categories WHERE is_default = ? LIMIT 0, 1', true);
        });
    }

    /**
     * Set default expense category.
     *
     * @return ExpenseCategory
     */
    public static function setDefault(ExpenseCategory $category)
    {
        if ($category->getIsDefault()) {
            return $category;
        }

        DB::transact(function () use ($category) {
            DB::execute('UPDATE expense_categories SET is_default = ?', false);
            DB::execute('UPDATE expense_categories SET is_default = ? WHERE id = ?', true, $category->getId());

            AngieApplication::invalidateInitialSettingsCache();
        }, 'Set default expense category');

        self::clearCache();

        return DataObjectPool::reload(ExpenseCategory::class, $category->getId());
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): ExpenseCategory
    {
        if ($archived_expense_category = self::getExistingExpenseCategoryByAttributes($attributes, true)) {
            return parent::update(
                $archived_expense_category,
                array_merge(
                    $attributes,
                    [
                        'is_archived' => false,
                    ]
                ),
                $save
            );
        }

        return parent::create($attributes, $save, $announce);
    }

    /**
     * Return expense category by attributes.
     *
     * @param  bool                 $is_archived
     * @return ExpenseCategory|null
     * @throws InvalidParamError
     */
    private static function getExistingExpenseCategoryByAttributes(array $attributes, $is_archived = false)
    {
        if ($existing_expense_category_id = DB::executeFirstCell('SELECT id FROM expense_categories WHERE name = ? AND is_archived = ?', array_var($attributes, 'name'), $is_archived)) {
            return self::findById($existing_expense_category_id);
        }

        return null;
    }

    /**
     * @return DataObject|ExpenseCategory|bool
     */
    public static function scrap(
        DataObject &$instance,
        bool $force_delete = false
    )
    {
        if ($instance->isUsed()) {
            return parent::update($instance, ['is_archived' => true], true);
        }

        return parent::scrap($instance, $force_delete);
    }

    /**
     * Edit an array of expense categories.
     *
     * sample call of this method:
     *
     * $results = ExpenseCategories::batchEdit([
     *     ['id' => 1, 'name' => 'foo'],
     *     ['id' => 2, 'name' => 'bar'],
     *     ['id' => 36, 'name' => 'baz', 'is_default' => false],
     * ]);
     *
     * @return array|ExpenseCategory[]
     * @throws InvalidParamError
     */
    public static function batchEdit(array $expense_categories)
    {
        $instances = [];
        foreach ($expense_categories as $expense_category) {
            /** @var ExpenseCategory $instance */
            if (($instance = DataObjectPool::get('ExpenseCategory', $expense_category['id']))) {
                $instance->setAttributes($expense_category);
                $instance->save();
                $instances[] = $instance;
            }
        }

        return $instances;
    }
}
