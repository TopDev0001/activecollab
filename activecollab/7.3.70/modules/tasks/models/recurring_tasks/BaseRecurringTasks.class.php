<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

abstract class BaseRecurringTasks extends DataManager
{
    /**
     * Return name of this model.
     */
    public static function getModelName(bool $underscore = false): string
    {
        return $underscore ? 'recurring_tasks' : 'RecurringTasks';
    }

    /**
     * Return name of the table where system will persist model instances.
     */
    public static function getTableName(): string
    {
        return 'recurring_tasks';
    }

    /**
     * All table fields.
     */
    private static array $fields = [
        'id',
        'project_id',
        'task_list_id',
        'assignee_id',
        'delegated_by_id',
        'name',
        'body',
        'body_mode',
        'is_important',
        'created_on',
        'created_by_id',
        'created_by_name',
        'created_by_email',
        'updated_on',
        'updated_by_id',
        'updated_by_name',
        'updated_by_email',
        'start_in',
        'due_in',
        'job_type_id',
        'estimate',
        'position',
        'is_hidden_from_clients',
        'is_trashed',
        'original_is_trashed',
        'trashed_on',
        'trashed_by_id',
        'repeat_frequency',
        'repeat_amount',
        'repeat_amount_extended',
        'triggered_number',
        'last_trigger_on',
        'fake_assignee_name',
        'fake_assignee_email',
        'raw_additional_properties',
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
        return RecurringTask::class;
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
        return '';
    }

    /**
     * @return RecurringTask[]|DbResult|null
     */
    public static function find($arguments = null): ?iterable
    {
        return parent::find($arguments);
    }

    public static function findOne($arguments = null): ?RecurringTask
    {
        return parent::findOne($arguments);
    }

    /**
     * @return RecurringTask[]|DbResult|null
     */
    public static function findBySQL(...$arguments): ?iterable
    {
        return parent::findBySQL(...$arguments);
    }

    /**
     * @return RecurringTask[]|DbResult|null
     */
    public static function findBy($field, $value = null): ?iterable
    {
        return parent::findBy($field, $value);
    }

    public static function findOneBy($field, $value = null): ?RecurringTask
    {
        return parent::findOneBy($field, $value);
    }

    /**
     * @return RecurringTask[]|DbResult|null
     */
    public static function findByIds(array $ids, bool $ordered_by_ids = false): ?iterable
    {
        return parent::findByIds($ids, $ordered_by_ids);
    }

    /**
     * @return RecurringTask[]
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
    ): RecurringTask
    {
        return parent::create($attributes, $save, $announce);
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): RecurringTask
    {
        return parent::update($instance, $attributes, $save);
    }

    /**
     * @return RecurringTask|bool
     */
    public static function scrap(
        DataObject &$instance,
        bool $force_delete = false
    )
    {
        return parent::scrap($instance, $force_delete);
    }
}
