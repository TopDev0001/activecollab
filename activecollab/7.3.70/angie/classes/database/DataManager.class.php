<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

abstract class DataManager
{
    public const ALL = 'all';
    public const ACTIVE = 'active';
    public const ARCHIVED = 'archived';

    /**
     * How do we know which class name to use.
     *
     * - CLASS_NAME_FROM_TABLE - Class name from table name, value is prepared
     *   by generator
     * - CLASS_NAME_FROM_FIELD - Load class name from row field
     */
    public const CLASS_NAME_FROM_TABLE = 0;
    public const CLASS_NAME_FROM_FIELD = 1;

    abstract public static function getModelName(bool $underscore = false): string;
    abstract public static function getInstanceClassName(): string;
    abstract public static function getTableName(): string;
    abstract public static function getFields(): array;
    abstract public static function getInstanceClassNameFrom(): int;
    abstract public static function getInstanceClassNameFromField(): string;
    abstract public static function getDefaultOrderBy(): string;

    public static function clearCache()
    {
        AngieApplication::cache()->removeByModel(static::getModelName(true));
        DataObjectPool::forget(static::getInstanceClassName());
    }

    /**
     * Clear cache for a particular object.
     *
     * @param array|int $object_ids
     */
    public static function clearCacheFor($object_ids)
    {
        $object_ids = $object_ids ? (array) $object_ids : [];

        foreach ($object_ids as $object_id) {
            AngieApplication::cache()->remove(get_data_object_cache_key(static::getModelName(true), $object_id));
        }

        DataObjectPool::forget(static::getInstanceClassName(), $object_ids);
    }

    public static function parentToCondition(
        DataObject $parent,
        bool $include_state_check = false
    ): string
    {
        $table_name = static::getTableName();

        if (!$parent instanceof ApplicationObject) {
            throw new InvalidInstanceError('parent', $parent, DataObject::class);
        }

        if ($include_state_check && $parent instanceof ITrash && !$parent->getIsTrashed()) {
            $state_check = DB::prepare("$table_name.is_trashed = ?", false);
        }

        if (isset($state_check)) {
            return DB::prepare("($table_name.parent_type = ? AND $table_name.parent_id = ? AND $state_check)", get_class($parent), $parent->getId());
        } else {
            return DB::prepare("($table_name.parent_type = ? AND $table_name.parent_id = ?)", get_class($parent), $parent->getId());
        }
    }

    /**
     * Parent not set condition.
     */
    public static function parentNotSetCondition(): string
    {
        $table_name = static::getTableName();

        return DB::prepare("($table_name.parent_type IS NULL AND ($table_name.parent_id IS NULL OR $table_name.parent_id = ''))");
    }

    /**
     * Check model Etag.
     *
     * @param  int    $id
     * @param  string $hash
     * @return bool
     */
    public static function checkObjectEtag($id, $hash)
    {
        if (static::fieldExists('updated_on')) {
            if ($updated_on = DB::executeFirstCell('SELECT updated_on FROM ' . static::getTableName() . ' WHERE id = ?', $id)) {
                return $hash == sha1(APPLICATION_UNIQUE_KEY . $updated_on);
            }
        }

        return false;
    }

    public static function fieldExists(string $field_name): bool
    {
        return in_array($field_name, static::getFields());
    }

    /**
     * Batch add records to this model.
     *
     * Example:
     *
     * Managers::createMany([
     *   [ 'first_name' => 'Peter', 'last_name' => 'Smith' ],
     *   [ 'first_name' => 'Joe', 'last_name' => 'Peterson' ],
     *   [ 'first_name' => 'Eric', 'last_name' => 'Miller' ],
     * ]);
     *
     * In case of polimorph models, key of each records should be class name of the particular records:
     *
     * ProjectObjects::add([
     *   [ 'type' => 'Task', 'project_id' => 12, 'name' => 'Do 100 pushups' ],
     *   [ 'type' => 'Discussion', 'project_id' => 12, 'name' => 'April fools', 'body' => 'Should we do something crazy this year?' ],
     * ]);
     *
     * Records added using this function will be automatically saved and returned.
     *
     * @return DataObject[]
     */
    public static function createMany(
        array $records,
        bool $save = true,
        bool $announce = true
    ): iterable
    {
        $result = [];

        try {
            DB::beginWork('Batch add records @ ' . __CLASS__);

            foreach ($records as $record) {
                $result[] = static::create($record, $save, $announce);
            }

            DB::commit('Records added @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to add records @ ' . __CLASS__);

            throw $e;
        }

        return $result;
    }

    /**
     * Create a new instance from attributes.
     *
     * Note: In case of polymorh model, 'type' attribute is required and it will determine which exact instance this
     * method will create and return. Example:
     *
     * ProjectObjects::create([ 'type' => 'Milestone', 'name' => 'First Sprint' ]);
     */
    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): DataObject
    {
        $class_name = static::getInstanceClassNameFrom() == self::CLASS_NAME_FROM_FIELD
            ? self::getInstanceClassNameFromAttributes($attributes)
            : static::getInstanceClassName();

        /** @var DataObject $instance */
        $instance = new $class_name();

        if ($attributes && is_foreachable($attributes)) {
            foreach ($attributes as $k => $v) {
                if ($instance->fieldExists($k)) {
                    if (str_ends_with($k, '_id')) {
                        // @TODO Trick to get all FK-s casted to int. This should be handled by
                        // @TODO DataObject::setFieldValue() actually (if field can't be NULL, cast it before value is
                        // @TODO remembered)
                        $v = (int) $v;
                    }

                    $instance->setFieldValue($k, $v);
                } else {
                    $instance->setAttribute($k, $v);
                }
            }
        }

        if ($save) {
            $instance->save();

            DataObjectPool::introduce($instance);
        }

        return $instance;
    }

    /**
     * Get instance class name from attributes.
     *
     * @param  array  $attributes
     * @return string
     */
    protected static function getInstanceClassNameFromAttributes($attributes)
    {
        $class_name = isset($attributes['type']) && $attributes['type'] ? $attributes['type'] : null;

        if (is_string($class_name) && class_exists($class_name)) {
            $instance_class_name = static::getInstanceClassName();

            if ($class_name != $instance_class_name && !is_subclass_of($class_name, $instance_class_name)) {
                throw new InvalidParamError('attributes[type]', $class_name, "Class '$class_name' does not extend '$instance_class_name'");
            }
        } else {
            throw new InvalidParamError('attributes[type]', $class_name, 'Value of "type" field is required for this model');
        }

        return $class_name;
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): DataObject
    {
        if ($attributes && is_foreachable($attributes)) {
            foreach ($attributes as $k => $v) {
                if ($instance->fieldExists($k)) {
                    if (str_ends_with($k, '_id')) {
                        $v = (int) $v; // @TODO Trick to get all FK-s casted to int. This should be handled by DataObject::setFieldValue() actually (if field can't be NULL, cast it before value is remembered)
                    }

                    $instance->setFieldValue($k, $v);
                } else {
                    $instance->setAttribute($k, $v);
                }
            }
        }

        if ($save) {
            $instance->save();
        }

        return $instance;
    }

    public static function scrap(
        DataObject &$instance,
        bool $force_delete = false
    )
    {
        if ($instance instanceof ITrash && empty($force_delete)) {
            $instance->moveToTrash();

            return $instance;
        } else {
            $instance->delete();

            return true;
        }
    }

    public static function batchScrap(
        iterable $instances,
        bool $force_delete = false
    ): bool {
        DB::transact(
            function () use ($instances, $force_delete) {
                foreach ($instances as $instance) {
                    self::scrap($instance, $force_delete);
                }
            },
        );

        return true;
    }

    /**
     * Restore given instance to active state.
     *
     * @return DataObject
     */
    public static function &reactivate(Dataobject &$instance)
    {
        DB::transact(
            function () use (&$instance) {
                if ($instance instanceof ITrash && $instance->getIsTrashed()) {
                    $instance->restoreFromTrash();
                }

                if ($instance instanceof IArchive && $instance->getIsArchived()) {
                    $instance->restoreFromArchive();
                }
            },
        );

        return $instance;
    }

    /**
     * Find records where fields match the provided values.
     *
     * Example:
     *
     * Projects::findBy('created_by_id', 1);
     * Projects::findBy([ 'created_by_id' => 1, 'category_id' => null, 'label_id' => 15 ]);
     *
     * @param  string|array               $field
     * @param  mixed                      $value
     * @return DBResult|Dataobject[]|null
     */
    public static function findBy($field, $value = null): ?iterable
    {
        if (is_array($field)) {
            $conditions = [];

            foreach ($field as $k => $v) {
                $conditions[] = self::fieldAndValueForFindBy($k, $v);
            }

            $conditions = implode(' AND ', $conditions);
        } else {
            $conditions = self::fieldAndValueForFindBy($field, $value);
        }

        return static::find(['conditions' => $conditions]);
    }

    /**
     * @param mixed $value
     */
    private static function fieldAndValueForFindBy(string $field, $value): string
    {
        if (!is_array($value)) {
            return $value === null
                ? "`$field` IS NULL"
                : DB::prepare("`$field` = ?", $value);
        }

        if (empty($value)) {
            throw new InvalidParamError('value', $value, '$value can not be an empty array');
        }

        return DB::prepare("`$field` IN (?)", $value);
    }

    /**
     * Do a SELECT query over database with specified arguments.
     *
     * This function can return single instance or array of instances that match
     * requirements provided in $arguments associative array
     *
     * $arguments is an associative array with following fields (all optional):
     *
     *  - one        - select first row
     *  - conditions - additional conditions
     *  - group      - group by string
     *  - having     - having string
     *  - order      - order by string
     *  - offset     - limit offset, valid only if limit is present
     *  - limit      - number of rows that need to be returned
     *
     * @param  array                      $arguments
     * @return DBResult|DataObject[]|null
     */
    public static function find($arguments = null): ?iterable
    {
        if (!empty($arguments['one'])) {
            throw new InvalidArgumentException('Argument "one" is no longer supported. Use findOne() instead.');
        }

        return static::findBySQL(static::prepareSelectFromArguments($arguments));
    }

    public static function findOne($arguments = null): ?DataObject
    {
        return static::findOneBySQL(static::prepareSelectFromArguments($arguments));
    }

    /**
     * Find a single instance by SQL.
     */
    public static function findOneBySql(...$arguments): ?DataObject
    {
        if (empty($arguments)) {
            throw new InvalidParamError(
                'arguments',
                $arguments,
                'DataManager::findOneBySql() function requires at least SQL query to be provided',
            );
        }

        $sql = array_shift($arguments);

        if (count($arguments)) {
            $sql = DB::getConnection()->prepare($sql, $arguments);
        }

        if ($row = DB::executeFirstRow($sql)) {
            switch (static::getInstanceClassNameFrom()) {
                case self::CLASS_NAME_FROM_FIELD:
                    $class_name = $row[static::getInstanceClassNameFromField()];

                    break;
                case self::CLASS_NAME_FROM_TABLE:
                    $class_name = static::getInstanceClassName();

                    break;
                default:
                    throw new Error('Unknown load instance class name from method: ' . static::getInstanceClassNameFrom());
            }

            /** @var DataObject $item */
            $item = new $class_name();
            $item->loadFromRow($row, true);

            return $item;
        } else {
            return null;
        }
    }

    /**
     * Prepare SELECT query string from arguments and table name.
     *
     * @param  array  $arguments
     * @return string
     */
    public static function prepareSelectFromArguments($arguments = null)
    {
        $one = !empty($arguments['one']);
        $conditions = isset($arguments['conditions']) ? DB::prepareConditions($arguments['conditions']) : '';
        $group_by = $arguments['group'] ?? '';
        $having = $arguments['having'] ?? '';
        $order_by = $arguments['order'] ?? static::getDefaultOrderBy();
        $offset = isset($arguments['offset']) ? (int) $arguments['offset'] : 0;
        $limit = isset($arguments['limit']) ? (int) $arguments['limit'] : 0;

        if ($one && $offset == 0 && $limit == 0) {
            $limit = 1; // Narrow the query
        }

        $table_name = static::getTableName();
        $where_string = trim($conditions) == '' ? '' : "WHERE $conditions";
        $group_by_string = trim($group_by) == '' ? '' : "GROUP BY $group_by";
        $having_string = trim($having) == '' ? '' : "HAVING $having";
        $order_by_string = trim($order_by) == '' ? '' : "ORDER BY $order_by";
        $limit_string = $limit > 0 ? "LIMIT $offset, $limit" : '';

        return sprintf(
            'SELECT * FROM %s %s %s %s %s %s',
            $table_name,
            $where_string,
            $group_by_string,
            $having_string,
            $order_by_string,
            $limit_string,
        );
    }

    /**
     * Return object of a specific class by SQL.
     *
     * @return DBResult|DataObject[]
     */
    public static function findBySQL(...$arguments): ?iterable
    {
        if (empty($arguments)) {
            throw new InvalidParamError(
                'arguments',
                $arguments,
                'DataManager::findOneBySql() function requires at least SQL query to be provided',
            );
        }

        $sql = array_shift($arguments);

        if ($arguments !== null) {
            $sql = DB::getConnection()->prepare($sql, $arguments);
        }

        $class_name_from = static::getInstanceClassNameFrom();

        switch ($class_name_from) {
            case self::CLASS_NAME_FROM_FIELD:
                return DB::getConnection()->execute($sql, null, DB::LOAD_ALL_ROWS, DB::RETURN_OBJECT_BY_FIELD, static::getInstanceClassNameFromField());
            case self::CLASS_NAME_FROM_TABLE:
                return DB::getConnection()->execute($sql, null, DB::LOAD_ALL_ROWS, DB::RETURN_OBJECT_BY_CLASS, static::getInstanceClassName());
            default:
                throw new InvalidParamError('class_name_from', $class_name_from, 'Unexpected value');
        }
    }

    /**
     * Find first record where fields match the provided values.
     *
     * Example:
     *
     * Projects::findOneBy('created_by_id', 1);
     * Projects::findOneBy([ 'created_by_id' => 1, 'category_id' => null, 'label_id' => 15 ]);
     *
     * @param string|array $field
     * @param mixed        $value
     */
    public static function findOneBy($field, $value = null): ?DataObject
    {
        if (is_array($field)) {
            $conditions = [];

            foreach ($field as $k => $v) {
                $conditions[] = self::fieldAndValueForFindBy($k, $v);
            }

            $conditions = implode(' AND ', $conditions);
        } else {
            $conditions = self::fieldAndValueForFindBy($field, $value);
        }

        return static::findOne(
            [
                'conditions' => $conditions,
            ],
        );
    }

    /**
     * Return multiple records by their ID-s.
     *
     * @return DBResult|DataObject
     */
    public static function findByIds(array $ids, bool $ordered_by_ids = false): ?iterable
    {
        if ($ordered_by_ids) {
            $escaped_ids = DB::escape($ids);

            return static::findBySQL(
                sprintf(
                    'SELECT * FROM `%s` WHERE `id` IN (%s) ORDER BY FIELD (`id`, %s)',
                    static::getTableName(),
                    $escaped_ids,
                    $escaped_ids,
                ),
            );
        }

        return static::find(
            [
                'conditions' => ['id IN (?)', $ids],
            ],
        );
    }

    /**
     * Return paginated result.
     *
     * This function will return paginated result as array. First element of
     * returned array is array of items that match the request. Second parameter
     * is Pager class instance that holds pagination data (total pages, current
     * and next page and so on)
     *
     * @param  array $arguments
     * @param  int   $page
     * @param  int   $per_page
     * @return array
     */
    public static function paginate($arguments = null, $page = 1, $per_page = 10)
    {
        if (empty($arguments)) {
            $arguments = [];
        }

        $arguments['limit'] = $per_page;
        $arguments['offset'] = ($page - 1) * $per_page;

        $conditions = isset($arguments['conditions']) && $arguments['conditions'] ? $arguments['conditions'] : null;

        return [static::find($arguments), new DBResultPager(static::count($conditions), $page, $per_page)];
    }

    /**
     * Return number of rows in this table.
     *
     * @param  string $conditions Query conditions
     * @return int
     */
    public static function count($conditions = null)
    {
        $table_name = static::getTableName();

        $conditions = trim(DB::prepareConditions($conditions) ?? '');

        if ($conditions) {
            return DB::executeFirstCell(
                sprintf("SELECT COUNT(`id`) AS 'row_count' FROM %s WHERE %s", $table_name, $conditions),
            );
        }

        return DB::executeFirstCell(sprintf("SELECT COUNT(`id`) AS 'row_count' FROM %s", $table_name));
    }

    /**
     * Return object by ID.
     *
     * @param  mixed      $id
     * @return DataObject
     */
    public static function findById($id)
    {
        if (empty($id)) {
            return null;
        }

        if (!is_numeric($id)) {
            throw new InvalidParamError('id', $id, '$id can only be a number');
        }

        $table_name = static::getTableName();

        $cached_row = AngieApplication::cache()->get(
            static::getCacheKeyForObject($id),
            function () use ($table_name, $id) {
                return DB::executeFirstRow(
                    sprintf(
                        'SELECT * FROM %s WHERE id = ? LIMIT 0, 1',
                        $table_name,
                    ),
                    $id,
                );
            },
        );

        if ($cached_row) {
            $class_name_from = static::getInstanceClassNameFrom();

            switch ($class_name_from) {
                case self::CLASS_NAME_FROM_FIELD:
                    $class_name = $cached_row[static::getInstanceClassNameFromField()];

                    break;
                case self::CLASS_NAME_FROM_TABLE:
                    $class_name = static::getInstanceClassName();

                    break;
                default:
                    throw new InvalidParamError(
                        'class_name_from',
                        $class_name_from,
                        'Unexpected value',
                    );
            }

            /** @var DataObject $item */
            $item = new $class_name();
            $item->loadFromRow($cached_row);

            return $item;
        } else {
            return null;
        }
    }

    /**
     * Get cache key for a given object.
     *
     * @param  DataObject|int $object_or_object_id
     * @param  mixed          $subnamespace
     * @return array
     */
    public static function getCacheKeyForObject($object_or_object_id, $subnamespace = null)
    {
        $instance_class = static::getInstanceClassName();

        if ($object_or_object_id instanceof $instance_class) {
            return get_data_object_cache_key(static::getModelName(true), $object_or_object_id->getId(), $subnamespace);
        } elseif (is_numeric($object_or_object_id)) {
            return get_data_object_cache_key(static::getModelName(true), $object_or_object_id, $subnamespace);
        }

        throw new InvalidParamError(
            'object_or_object_id',
            $object_or_object_id,
            sprintf('object_or_object_id needs to either instance of %s or ID', $instance_class),
        );
    }

    /**
     * Return model level cache key.
     *
     * @param  array|string|null $subnamespace
     * @return array
     */
    public static function getCacheKey($subnamespace = null)
    {
        $key = [
            'models',
            static::getModelName(true),
        ];

        if ($subnamespace) {
            $subnamespace = (array) $subnamespace;

            if (count($subnamespace)) {
                $key = array_merge($key, $subnamespace);
            }
        }

        return $key;
    }

    /**
     * Delete all rows that match given conditions.
     *
     * @param  string $conditions Query conditions
     * @return bool
     */
    public static function delete($conditions = null)
    {
        $table_name = static::getTableName();

        if ($conditions = trim((string) DB::prepareConditions($conditions))) {
            return (bool) DB::execute(
                sprintf('DELETE FROM %s WHERE %s', $table_name, $conditions),
            );
        }

        return (bool) DB::execute(sprintf('DELETE FROM %s', $table_name));
    }

    /**
     * Drop records by parents.
     *
     * @param array|null $parents
     */
    public static function deleteByParents($parents)
    {
        if (static::fieldExists('parent_type') && static::fieldExists('parent_id')) {
            $conditions = static::typeIdsMapToConditions($parents);

            if ($conditions) {
                DB::execute('DELETE FROM ' . static::getTableName() . ' WHERE ' . $conditions);
            }
        } else {
            throw new NotImplementedError(__METHOD__, 'This model does not have parent_type and parent_id fields');
        }
    }

    /**
     * Prepare WHERE part based on a type => IDs map.
     *
     * @param  array       $type_ids_map
     * @param  string      $operation
     * @param  string      $parent_type_field
     * @param  string      $parent_id_field
     * @return string|null
     */
    public static function typeIdsMapToConditions($type_ids_map, $operation = 'OR', $parent_type_field = 'parent_type', $parent_id_field = 'parent_id')
    {
        if ($type_ids_map && is_foreachable($type_ids_map)) {
            $result = [];

            $table_name = static::getTableName();
            foreach ($type_ids_map as $type => $ids) {
                $result[] = DB::prepare("($table_name.$parent_type_field = ? AND $table_name.$parent_id_field IN (?))", $type, $ids);
            }

            return '(' . implode(" $operation ", $result) . ')';
        }

        return null;
    }

    // ---------------------------------------------------
    //  Objects and traits
    // ---------------------------------------------------

    /**
     * Return new collection.
     *
     * @param  User|null       $user
     * @return ModelCollection
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        return new ModelCollection($collection_name, static::getModelName());
    }

    public static function prepareCursorCollection(string $collection_name, User $user): CursorModelCollection
    {
        return new CursorModelCollection($collection_name, static::getModelName());
    }

    public static function prepareRelativeCursorCollection(
        string $collection_name,
        User $user
    ): RelativeCursorModelCollection
    {
        return new RelativeCursorModelCollection($collection_name, static::getModelName());
    }

    protected static array $traits_by_object = [];

    /**
     * Return trait names by object.
     *
     * @return array
     */
    public static function getTraitNamesByObject(ApplicationObject $object)
    {
        $class = get_class($object);

        if (!array_key_exists($class, self::$traits_by_object)) {
            self::$traits_by_object[$class] = [];

            self::recursiveGetTraitNames(new ReflectionClass($class), self::$traits_by_object[$class]);
        }

        return static::$traits_by_object[$class];
    }

    /**
     * Recursively get trait names for the given class.
     *
     * @param array $trait_names
     */
    private static function recursiveGetTraitNames(ReflectionClass $class, &$trait_names)
    {
        $trait_names = array_merge($trait_names, $class->getTraitNames());

        if ($class->getParentClass()) {
            static::recursiveGetTraitNames($class->getParentClass(), $trait_names);
        }
    }

    /**
     * Return true if we have a valid $manager_class.
     *
     * @param  string $manager_class
     * @return bool
     */
    public static function isManagerClass($manager_class)
    {
        return (new ReflectionClass($manager_class))->isSubclassOf(self::class);
    }
}
