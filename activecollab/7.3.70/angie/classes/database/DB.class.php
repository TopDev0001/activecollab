<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Database interface.
 *
 * @package angie.library.database
 */
final class DB
{
    /**
     * Load mode.
     *
     * LOAD_ALL_ROWS - Load all rows
     * LOAD_FIRST_ROW - Limit result set to first row and load it
     * LOAD_FIRST_COLUMN - Return content of first column
     * LOAD_FIRST_CELL - Load only first cell of first row
     */
    public const LOAD_ALL_ROWS = 0;
    public const LOAD_FIRST_ROW = 1;
    public const LOAD_FIRST_COLUMN = 2;
    public const LOAD_FIRST_CELL = 3;

    /**
     * Return method for DB results.
     *
     * RETURN_ARRAY - Return fields as associative array
     * RETURN_OBJECT_BY_CLASS - Create new object instance and hydrate it
     * RETURN_OBJECT_BY_FIELD - Read class from record field, create instance
     *   and hydrate it
     */
    public const RETURN_ARRAY = 0;
    public const RETURN_OBJECT_BY_CLASS = 1;
    public const RETURN_OBJECT_BY_FIELD = 2;

    /**
     * Array of open connections.
     *
     * Default connection is available at key 'default'
     *
     * @var DBConnection[]
     */
    private static $connections = [];

    /**
     * Return true if there's a connection named $name.
     *
     * @param  string $name
     * @return bool
     */
    public static function hasConnection($name = 'default')
    {
        return isset(self::$connections[$name]) && self::$connections[$name] instanceof DBConnection;
    }

    // Interface methods
    /**
     * Set connection.
     *
     * @param string $name
     */
    public static function setConnection($name, DBConnection $connection)
    {
        if ($connection->isConnected()) {
            self::$connections[$name] = $connection;
        } else {
            throw new InvalidParamError('connection', $connection, 'Connection needs to be open');
        }
    }

    /**
     * Execute query and return first row. If there is no first row NULL is returned.
     *
     * @param mixed
     * @return array
     */
    public static function executeFirstRow()
    {
        $arguments = func_get_args();
        if (empty($arguments)) {
            throw new InvalidParamError('arguments', $arguments, 'DB::executeFirstRow() function requires at least SQL query to be provided');
        } else {
            return self::$connections['default']->executeFirstRow(array_shift($arguments), $arguments);
        }
    }

    /**
     * Execute SQL query and return content of first column as an array.
     *
     * @param mixed
     * @return array|null
     */
    public static function executeFirstColumn()
    {
        $arguments = func_get_args();
        if (empty($arguments)) {
            throw new InvalidParamError('arguments', $arguments, 'DB::executeFirstColumn() function requires at least SQL query to be provided');
        } else {
            return self::$connections['default']->executeFirstColumn(array_shift($arguments), $arguments);
        }
    }

    /**
     * Return value from the first cell.
     *
     * @param mixed
     * @return mixed
     */
    public static function executeFirstCell()
    {
        $arguments = func_get_args();
        if (empty($arguments)) {
            throw new InvalidParamError('arguments', $arguments, 'DB::executeFirstCell() function requires at least SQL query to be provided');
        } else {
            return self::$connections['default']->executeFirstCell(array_shift($arguments), $arguments);
        }
    }

    /**
     * Execute a query and return ID name map.
     *
     * First parameter needs to be SQL query. Other parameters will be used as arguments to prepare the query. Last parameter can be a closure
     * that will extract name from the row
     *
     * DB::executeIdNameMap('SELECT id, name FROM users WHERE type = ?', 'Member');
     * DB::executeIdNameMap('SELECT id, name FROM users WHERE type = ?', 'Member', function($row) {
     *   return ucfirst($row['name']);
     * });
     *
     * @return array
     */
    public static function executeIdNameMap()
    {
        $arguments = func_get_args();

        if (empty($arguments)) {
            throw new InvalidParamError('arguments', $arguments, 'DB::executeIdNameMap() function requires at least SQL query to be provided');
        } else {
            $sql = array_shift($arguments);
            $get_name_callback = count($arguments) > 0 && $arguments[count($arguments) - 1] instanceof Closure ? array_pop($arguments) : null;

            if (count($arguments)) {
                $sql = self::$connections['default']->prepare($sql, $arguments);
            }

            $result = [];

            if ($rows = self::execute($sql)) {
                foreach ($rows as $row) {
                    if (empty($row['id'])) {
                        throw new InvalidParamError('arguments', $arguments, 'Records need to include id field');
                    }

                    $result[$row['id']] = $get_name_callback instanceof Closure ? $get_name_callback($row) : $row['name'];
                }
            }

            return $result;
        }
    }

    /**
     * Execute sql.
     *
     * @param mixed
     * @return DbResult
     */
    public static function execute()
    {
        $arguments = func_get_args();
        if (empty($arguments)) {
            throw new InvalidParamError('arguments', $arguments, 'DB::execute() function requires at least SQL query to be provided');
        } else {
            return self::$connections['default']->execute(array_shift($arguments), $arguments);
        }
    }

    /**
     * Return number of affected rows.
     *
     * @return int
     */
    public static function affectedRows()
    {
        return self::$connections['default']->affectedRows();
    }

    /**
     * Return last insert ID.
     *
     * @return int
     */
    public static function lastInsertId()
    {
        return self::$connections['default']->lastInsertId();
    }

    /**
     * Insert a record.
     *
     * @param string $table
     */
    public static function insertRecord($table, array $field_value_map)
    {
        array_walk($field_value_map, function (&$v) {
            $v = DB::escape($v);
        });

        $fields = implode(', ', array_keys($field_value_map));
        $values = implode(', ', array_values($field_value_map));

        self::execute("INSERT INTO $table ($fields) VALUES ($values)");
    }

    /**
     * Escape $unescaped value.
     *
     * @param  mixed  $unescaped
     * @return string
     */
    public static function escape($unescaped)
    {
        return self::$connections['default']->escape($unescaped);
    }

    /**
     * Run within a transation.
     *
     * There are two possible parameter combos:
     *
     * DB::transact(Transaction Closure, Operation Name, On Success Callback, On Error Callback)
     *
     * or:
     *
     * DB::transact(Transaction Closure, On Success Callback, On Error Callback)
     *
     * Value of $p1 will determine how system will check for parameters. If it is string, first set of parameters will
     * be used, if not, second will be used.
     *
     * @param string|callable|null $p1
     * @param callable|null        $p2
     * @param callable|null        $p3
     */
    public static function transact(callable $callback, $p1 = null, $p2 = null, $p3 = null)
    {
        if (is_string($p1)) {
            $operation = $p1;
            $on_success = $p2;
            $on_error = $p3;
        } else {
            $operation = null;
            $on_success = $p1;
            $on_error = $p2;
        }

        try {
            self::beginWork("Begin Work: $operation");

            call_user_func($callback);

            self::commit("Commit: $operation");

            if (is_callable($on_success)) {
                call_user_func($on_success);
            }
        } catch (Exception $e) {
            self::rollback("Rollback: $operation");

            $stop_propagation = false;

            if (is_callable($on_error)) {
                $stop_propagation = call_user_func($on_error, $e);
            }

            if ($stop_propagation !== true) {
                throw $e;
            }
        }
    }

    /**
     * Begin transaction.
     *
     * @param  string $message
     * @return bool
     */
    public static function beginWork($message = null)
    {
        return self::$connections['default']->beginWork($message);
    }

    /**
     * Commit transaction.
     *
     * @param  string $message
     * @return bool
     */
    public static function commit($message = null)
    {
        return self::$connections['default']->commit($message);
    }

    /**
     * Rollback transaction.
     *
     * @param  string $message
     * @return bool
     */
    public static function rollback($message = null)
    {
        return self::$connections['default']->rollback($message);
    }

    /**
     * Return true if system is in transaction.
     *
     * @return bool
     */
    public static function inTransaction()
    {
        return self::$connections['default']->inTransaction();
    }

    /**
     * Prepare a batch insert instance.
     *
     * @param  string        $table_name
     * @param  array         $fields
     * @param  int           $rows_per_batch
     * @return DBBatchInsert
     */
    public static function batchInsert($table_name, $fields, $rows_per_batch = 50)
    {
        return new DBBatchInsert($table_name, $fields, $rows_per_batch);
    }

    /**
     * Prepare SQL with given arguments.
     *
     * @return string
     */
    public static function prepare()
    {
        $arguments = func_get_args();

        if (empty($arguments)) {
            throw new InvalidParamError(
                'arguments',
                $arguments,
                'DB::prepare() function requires at least SQL query to be provided',
            );
        }

        return self::$connections['default']->prepare(array_shift($arguments), $arguments);
    }

    /**
     * Prepare conditions.
     *
     * @param  mixed       $conditions
     * @return string|null
     */
    public static function prepareConditions($conditions)
    {
        return is_array($conditions)
            ? self::getConnection()->prepare(array_shift($conditions), $conditions)
            : $conditions;
    }

    /**
     * Return connection instance by name.
     *
     * If $name is not provided, default DB connection will be used
     *
     * @param  string       $name
     * @return DBConnection
     */
    public static function &getConnection($name = 'default')
    {
        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        } else {
            throw new InvalidParamError('name', $name, "There is no '$name' connection open");
        }
    }

    // ---------------------------------------------------
    //  Engineer
    // ---------------------------------------------------

    /**
     * Escape field name.
     *
     * @param  mixed  $unescaped
     * @return string
     */
    public static function escapeFieldName($unescaped)
    {
        return self::$connections['default']->escapeFieldName($unescaped);
    }

    /**
     * Escape table name.
     *
     * @param  mixed  $unescaped
     * @return string
     */
    public static function escapeTableName($unescaped)
    {
        return self::$connections['default']->escapeTableName($unescaped);
    }

    /**
     * Return true if table $name exists.
     *
     * @param  string $name
     * @return bool
     */
    public static function tableExists($name)
    {
        return self::$connections['default']->tableExists($name);
    }

    /**
     * Create a new database table.
     *
     * @param  string  $name
     * @return DBTable
     */
    public static function createTable($name)
    {
        return self::$connections['default']->createTable($name);
    }

    /**
     * Load table details.
     *
     * @param  string  $name
     * @return DBTable
     */
    public static function loadTable($name)
    {
        return self::$connections['default']->loadTable($name);
    }

    /**
     * Return array of tables from the database.
     *
     * @param  string $prefix
     * @param  bool   $include_views
     * @return array
     */
    public static function listTables($prefix = null, $include_views = false)
    {
        return self::$connections['default']->listTables($prefix, $include_views);
    }

    /**
     * Return list of fields from given table.
     *
     * @param  string $table_name
     * @return array
     */
    public static function listTableFields($table_name)
    {
        return self::$connections['default']->listTableFields($table_name);
    }

    /**
     * Drop specific table.
     *
     * @param string $table_name
     */
    public static function dropTable($table_name)
    {
        self::dropTables([$table_name]);
    }

    /**
     * Drop one or more tables.
     *
     * @param array  $tables
     * @param string $prefix
     */
    public static function dropTables($tables, $prefix = '')
    {
        self::$connections['default']->dropTables($tables, $prefix);
    }

    /**
     * List indexes for a given table name.
     *
     * @param  string $table_name
     * @return array
     */
    public static function listTableIndexes($table_name)
    {
        return self::$connections['default']->listTableIndexes($table_name);
    }

    // ---------------------------------------------------
    //  File export / import
    // ---------------------------------------------------

    /**
     * Drop all tables from database.
     *
     * @return bool
     */
    public static function clearDatabase()
    {
        return self::$connections['default']->clearDatabase();
    }

    // ---------------------------------------------------
    //  Track
    // ---------------------------------------------------

    /**
     * Gets maximum packet size allowed to be inserted into database.
     *
     * @return int
     */
    public static function getMaxPacketSize()
    {
        return self::$connections['default']->getMaxPacketSize();
    }

    /**
     * Do a database dump of specified tables.
     *
     * If $table_name is empty it will dump all tables in current database
     *
     * @param array  $tables
     * @param string $output_file
     * @param bool   $dump_structure
     * @param bool   $dump_data
     */
    public static function exportToFile($tables, $output_file, $dump_structure = true, $dump_data = true)
    {
        self::$connections['default']->exportToFile($tables, $output_file, $dump_structure, $dump_data);
    }

    /**
     * Return all queries that DB layer logged.
     *
     * @return array
     */
    public static function getAllQueries()
    {
        return !empty(self::$connections['default']) ? self::$connections['default']->getAllQueries() : [];
    }

    /**
     * Return number of queries that are executed.
     *
     * @return int
     */
    public static function getQueryCount()
    {
        return !empty(self::$connections['default']) ? self::$connections['default']->getQueryCount() : 0;
    }

    /**
     * Return total time spent performing all queries.
     *
     * @return float
     */
    public static function getAllQueriesExecTime()
    {
        return !empty(self::$connections['default']) ? self::$connections['default']->getAllQueriesExecTime() : 0.0;
    }
}
