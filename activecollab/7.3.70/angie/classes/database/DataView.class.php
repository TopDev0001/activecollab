<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Data view class.
 *
 * Similar to data manager, but wraps around a view instead of a regular table
 *
 * @package angie.library.database
 */
abstract class DataView
{
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

    // ---------------------------------------------------
    //  Finders
    // ---------------------------------------------------

    /**
     * Return name of this model.
     *
     * @param  bool                $underscore
     * @return string
     * @throws NotImplementedError
     */
    public static function getModelName($underscore = false)
    {
        throw new NotImplementedError(__METHOD__);
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
     * @param  array    $arguments
     * @return DBResult
     */
    public static function find($arguments = null)
    {
        if ($arguments && isset($arguments['one']) && $arguments['one']) {
            return static::findOneBySQL(static::prepareSelectFromArguments($arguments));
        } else {
            return static::findBySQL(static::prepareSelectFromArguments($arguments));
        }
    }

    /**
     * Find a single instance by SQL.
     *
     * @return DBResult
     * @throws InvalidParamError
     */
    public static function findOneBySql()
    {
        $arguments = func_get_args();

        if (empty($arguments)) {
            throw new InvalidParamError('arguments', $arguments, 'DataManager::findOneBySql() function requires at least SQL query to be provided');
        }

        $sql = array_shift($arguments);

        if (count($arguments)) {
            $sql = DB::getConnection()->prepare($sql, $arguments);
        }

        if ($row = DB::executeFirstRow($sql)) {
            $class_name = $row['type'];

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
        $one = (bool) (isset($arguments['one']) && $arguments['one']);
        $conditions = isset($arguments['conditions']) ? DB::prepareConditions($arguments['conditions']) : '';
        $group_by = isset($arguments['group']) ? $arguments['group'] : '';
        $having = isset($arguments['having']) ? $arguments['having'] : '';
        $order_by = isset($arguments['order']) ? $arguments['order'] : static::getDefaultOrderBy();
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

        return "SELECT * FROM $table_name $where_string $group_by_string $having_string $order_by_string $limit_string";
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * Return name of this model.
     *
     * @return string
     * @throws NotImplementedError
     */
    public static function getDefaultOrderBy()
    {
        return 'id';
    }

    /**
     * Return name of the table where system will persist model instances.
     *
     * @param  bool                $with_prefix
     * @return string
     * @throws NotImplementedError
     */
    public static function getTableName($with_prefix = true)
    {
        throw new NotImplementedError(__METHOD__);
    }

    /**
     * Return object of a specific class by SQL.
     *
     * @return DBResult
     * @throws InvalidParamError
     */
    public static function findBySQL()
    {
        $arguments = func_get_args();

        if (empty($arguments)) {
            throw new InvalidParamError('arguments', $arguments, 'DataManager::findOneBySql() function requires at least SQL query to be provided');
        }

        $sql = array_shift($arguments);

        if ($arguments !== null) {
            $sql = DB::getConnection()->prepare($sql, $arguments);
        }

        return DB::getConnection()->execute($sql, null, DB::LOAD_ALL_ROWS, DB::RETURN_OBJECT_BY_FIELD, 'type');
    }

    /**
     * Return true if $field_name exists in this model.
     *
     * @param  string $field_name
     * @return bool
     */
    public static function fieldExists($field_name)
    {
        return in_array($field_name, static::getFields());
    }

    /**
     * Return all model fields.
     *
     * @return array
     * @throws NotImplementedError
     */
    public static function getFields()
    {
        throw new NotImplementedError(__METHOD__);
    }
}
