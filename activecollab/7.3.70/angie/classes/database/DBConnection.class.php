<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Abstract DBConnection class.
 *
 * @package angie.db.connections
 */
abstract class DBConnection
{
    /**
     * TRUE if we have established connection to the database.
     *
     * @var bool
     */
    protected $is_connected = false;

    /**
     * Open connection to the server.
     *
     * @param array $parameters
     */
    abstract public function connect($parameters);

    /**
     * Reconnect to the server.
     */
    abstract public function reconnect();

    /**
     * Disconnect from the server.
     */
    abstract public function disconnect();

    /**
     * Return database connection handle.
     *
     * @return mysqli|resource
     */
    abstract public function &getLink();

    /**
     * Returns true if we have a connection to the database.
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->is_connected;
    }

    /**
     * Return number of affected rows.
     *
     * @param void
     * @return int
     */
    abstract public function affectedRows();

    /**
     * Return last insert ID.
     *
     * @param void
     * @return int
     */
    abstract public function lastInsertId();

    /**
     * Begin transaction.
     *
     * @param string $message
     */
    abstract public function beginWork($message = null);

    /**
     * Commit transaction.
     *
     * @param string $message
     */
    abstract public function commit($message = null);

    /**
     * Rollback transaction.
     *
     * @param string $message
     */
    abstract public function rollback($message = null);

    /**
     * Return true if system is in transaction.
     *
     * @return bool
     */
    abstract public function inTransaction();

    /**
     * Escape table field name.
     *
     * @param  string $unescaped
     * @return string
     */
    abstract public function escapeFieldName($unescaped);

    /**
     * Escape table name.
     *
     * @param  string $unescaped
     * @return string
     */
    abstract public function escapeTableName($unescaped);

    /**
     * Execute query and return first row. If there is no first row NULL is returned.
     *
     * @param  string $sql
     * @param  array  $arguments
     * @param  int    $return_mode
     * @param  null   $return_class_or_field
     * @return array
     */
    public function executeFirstRow(
        $sql,
        $arguments = null,
        $return_mode = DB::RETURN_ARRAY,
        $return_class_or_field = null
    )
    {
        return $this->execute($sql, $arguments, DB::LOAD_FIRST_ROW, $return_mode, $return_class_or_field);
    }

    /**
     * Execute SQL query and optionally prepare arguments.
     *
     * @param  string               $sql
     * @param  array                $arguments
     * @param  int                  $load
     * @param  int                  $return_mode
     * @param  string               $return_class_or_field
     * @return DBResult|array|mixed
     * @throws DBQueryError
     */
    abstract public function execute(
        $sql,
        $arguments = null,
        $load = DB::LOAD_ALL_ROWS,
        $return_mode = DB::RETURN_ARRAY,
        $return_class_or_field = null
    );

    /**
     * Return values from the first column as an array.
     *
     * @param  string       $sql
     * @param  array        $arguments
     * @return mixed
     * @throws DBQueryError
     */
    public function executeFirstColumn($sql, $arguments = null)
    {
        return $this->execute($sql, $arguments, DB::LOAD_FIRST_COLUMN);
    }

    /**
     * Return value from the first cell.
     *
     * @param  string       $sql
     * @param  array        $arguments
     * @return mixed
     * @throws DBQueryError
     */
    public function executeFirstCell($sql, $arguments = null)
    {
        return $this->execute($sql, $arguments, DB::LOAD_FIRST_CELL);
    }

    /**
     * Prepare SQL (replace ? with data from $arguments array).
     *
     * @param  string $sql
     * @param  array  $arguments
     * @return string
     */
    public function prepare($sql, $arguments = null)
    {
        if ($arguments && is_foreachable($arguments)) {
            $offset = 0;
            foreach ($arguments as $argument) {
                $question_mark_pos = strpos_utf($sql, '?', $offset);
                if ($question_mark_pos !== false) {
                    $escaped = $this->escape($argument);
                    $escaped_len = strlen_utf($escaped);

                    $sql = substr_utf($sql, 0, $question_mark_pos) . $escaped . substr_utf($sql, $question_mark_pos + 1, strlen_utf($sql));

                    $offset = $question_mark_pos + $escaped_len;
                }
            }
        }

        return $sql;
    }

    /**
     * Escape value and prepare it for use in the query.
     *
     * @param  string|array $unescaped
     * @return string
     */
    abstract public function escape($unescaped);

    /**
     * Returns true if table $name exists.
     *
     * @param  string $name
     * @return bool
     */
    abstract public function tableExists($name);

    // ---------------------------------------------------
    //  Table management
    // ---------------------------------------------------

    /**
     * Create new table instance.
     *
     * @param  string  $name
     * @return DBTable
     */
    abstract public function createTable($name);

    /**
     * Load table information.
     *
     * @param bool $name
     */
    abstract public function loadTable($name);

    /**
     * Return array of tables from the database.
     *
     * @param  string $prefix
     * @param  bool   $include_views
     * @return array
     */
    abstract public function listTables($prefix = null, $include_views = false);

    /**
     * Return list of fields from given table.
     *
     * @param  string $table_name
     * @return array
     */
    abstract public function listTableFields($table_name);

    /**
     * Drop one or more tables.
     *
     * @param array  $tables
     * @param string $prefix
     */
    abstract public function dropTables($tables, $prefix = '');

    /**
     * List indexes form a given table name.
     *
     * @abstract
     * @param $table_name
     * @return array
     */
    abstract public function listTableIndexes($table_name);

    /**
     * Drop all tables from database.
     *
     * @return bool
     */
    abstract public function clearDatabase();

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
    abstract public function exportToFile($tables, $output_file, $dump_structure = true, $dump_data = true);

    // ---------------------------------------------------
    //  File export / import
    // ---------------------------------------------------

    /**
     * Get server variable value.
     *
     * @param  string $variable_name
     * @return mixed
     */
    abstract public function getServerVariable($variable_name);

    // ---------------------------------------------------
    //  Server variables
    // ---------------------------------------------------

    /**
     * Return version of the server.
     *
     * @return string
     */
    abstract public function getServerVersion();

    /**
     * Gets maximum packet size allowed to be inserted into database.
     *
     * @return int
     */
    abstract public function getMaxPacketSize();

    /**
     * Return all queries that DB layer logged.
     *
     * @return array
     */
    abstract public function getAllQueries();

    /**
     * Return number of queries that are executed.
     *
     * @return int
     */
    abstract public function getQueryCount();

    /**
     * Return total time spent performing all queries.
     *
     * @return float
     */
    abstract public function getAllQueriesExecTime();

    /**
     * Convert row to expected result.
     *
     * @param  array                $row
     * @param  int                  $return_mode
     * @param  int                  $return_class_or_field
     * @return mixed
     * @throws InvalidInstanceError
     */
    protected function rowToResult($row, $return_mode, $return_class_or_field)
    {
        switch ($return_mode) {
            // We have class name provided as a parameter
            case DB::RETURN_OBJECT_BY_CLASS:
                $class_name = $return_class_or_field;

                $object = new $class_name();
                if ($object instanceof DataObject) {
                    $object->loadFromRow($row);

                    return $object;
                } else {
                    throw new InvalidInstanceError('object', $object, DataObject::class);
                }

            // Get class from field value, and contruct and hidrate object
            // no break
            case DB::RETURN_OBJECT_BY_FIELD:
                $class_name = $row[$return_class_or_field];

                $object = new $class_name();
                if ($object instanceof DataObject) {
                    $object->loadFromRow($row);

                    return $object;
                } else {
                    throw new InvalidInstanceError('object', $object, DataObject::class);
                }

            // Plain assoc array
            // no break
            default:
                return $row;
        }
    }
}
