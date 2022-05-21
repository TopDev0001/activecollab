<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

defined('INCREASE_GROUP_CONCAT_MAX_LEN') or define('INCREASE_GROUP_CONCAT_MAX_LEN', true);

final class MySQLDBConnection extends DBConnection
{
    /**
     * MySQLi connection.
     *
     * @var MySQLi
     */
    protected $link;

    /**
     * Cached connection parameters.
     *
     * @var array
     */
    protected $connection_parameters;

    /**
     * Transaction level.
     *
     * @var int
     */
    private $transaction_level = 0;

    /**
     * Construct MySQLDBConnection instance.
     *
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string $database
     */
    public function __construct($host, $user, $pass, $database)
    {
        $this->connect(
            [
                'host' => $host,
                'user' => $user,
                'pass' => $pass,
                'db_name' => $database,
            ]
        );
    }

    /**
     * Connect to database.
     *
     * @param array $parameters
     */
    public function connect($parameters)
    {
        $this->link = new mysqli(
            $parameters['host'],
            $parameters['user'],
            $parameters['pass'],
            $parameters['db_name']
        );

        if ($this->link->connect_errno) {
            throw new DBConnectError($parameters['host'], $parameters['user'], $parameters['pass'], $parameters['db_name'], 'Failed to select database. Reason: ' . $this->link->connect_error);
        }

        $this->link->query('SET NAMES utf8mb4');

        // Make sure that we can use GROUP_CONCAT for collection hash calculations
        if (INCREASE_GROUP_CONCAT_MAX_LEN) {
            $max_allowed_packet = (int) $this->getServerVariable('max_allowed_packet');

            if ($max_allowed_packet) {
                $this->setSessionVariable('group_concat_max_len', $max_allowed_packet > 4294967295 ? 4294967295 : $max_allowed_packet); // Max value for group_concat_max_len is 4294967295
            }
        }

        $this->connection_parameters = $parameters;
        $this->is_connected = true;
    }

    /**
     * Get MySQL variable value.
     *
     * @param  string $variable_name
     * @return mixed
     */
    public function getServerVariable($variable_name)
    {
        $variable = $this->executeFirstRow("SHOW VARIABLES LIKE '$variable_name'");

        return is_array($variable) && isset($variable['Value']) ? $variable['Value'] : null;
    }

    /**
     * Set session variable value.
     *
     * @param string $variable_name
     * @param mixed  $value
     */
    public function setSessionVariable($variable_name, $value)
    {
        $this->execute("SET SESSION $variable_name = $value");
    }

    /**
     * Execute SQL query.
     *
     * @param  string                                       $sql
     * @param  mixed                                        $arguments
     * @param  int                                          $load
     * @param  int                                          $return_mode
     * @param  string                                       $return_class_or_field
     * @return array|bool|DBResult|mixed|MySQLDBResult|null
     * @throws DBQueryError
     * @throws DBNotConnectedError
     * @throws Exception
     */
    public function execute($sql, $arguments = null, $load = DB::LOAD_ALL_ROWS, $return_mode = DB::RETURN_ARRAY, $return_class_or_field = null)
    {
        $query_result = $this->executeQuery($sql, $arguments);

        // Handle query error
        if ($query_result === false) {
            switch ($this->link->errno) {
                // Non-transactional tables not rolled back!
                case 1196:
                    return null;

                // Server gone away
                case 2006:
                case 2013:
                    $query_result = $this->handleMySqlGoneAway($sql, $arguments);
                    break;

                // Deadlock detection and retry
                case 1213:
                    $query_result = $this->handleDeadlock($sql, $arguments);
                    break;

                // Other error
                default:
                    throw new DBQueryError($sql, $this->link->errno, $this->link->error);
            }
        }

        if ($query_result instanceof mysqli_result) {
            if ($query_result->num_rows > 0) {
                switch ($load) {
                    case DB::LOAD_FIRST_ROW:
                        $result = self::rowToResult($query_result->fetch_assoc(), $return_mode, $return_class_or_field);
                        break;

                    case DB::LOAD_FIRST_COLUMN:
                        $result = [];

                        if ($query_result->num_rows > 0) {
                            $cast = null;

                            while ($row = $query_result->fetch_assoc()) {
                                foreach ($row as $k => $v) {
                                    if (empty($cast)) {
                                        if ($k == 'id' || str_ends_with($k, '_id')) {
                                            $cast = DBResult::CAST_INT;
                                        } elseif (str_starts_with($k, 'is_')) {
                                            $cast = DBResult::CAST_BOOL;
                                        } else {
                                            $cast = DBResult::CAST_STRING;
                                        }
                                    }

                                    if ($cast == DBResult::CAST_INT) {
                                        $result[] = (int) $v;
                                    } elseif ($cast == DBResult::CAST_BOOL) {
                                        $result[] = (bool) $v;
                                    } else {
                                        $result[] = $v;
                                    }

                                    break;
                                }
                                //$result[] = array_shift($row);
                            }
                        }

                        break;

                    case DB::LOAD_FIRST_CELL:
                        $result = null;

                        foreach ($query_result->fetch_assoc() as $k => $v) {
                            if ($k == 'id' || $k == 'row_count' || str_ends_with($k, '_id')) {
                                $result = (int) $v;
                            } elseif (str_starts_with($k, 'is_')) {
                                $result = (bool) $v;
                            } else {
                                $result = $v;
                            }

                            break;
                        }

                        break;
                    default:
                        return new MySQLDBResult($query_result, $return_mode, $return_class_or_field); // Don't close result, we need it
                }
            } else {
                $result = null;
            }

            $query_result->close();

            return $result;
        } elseif ($query_result === true) {
            return true;
        } else {
            throw new DBQueryError($sql, $this->link->errno, $this->link->error);
        }
    }

    /**
     * Prepare (if needed) and execute SQL query.
     *
     * @param  string              $sql
     * @param  array|null          $arguments
     * @return bool|mysqli_result
     * @throws DBNotConnectedError
     */
    private function executeQuery($sql, $arguments = null)
    {
        if (empty($this->link)) {
            throw new DBNotConnectedError();
        }

        $microtime = microtime(true);

        $prepared_sql = $this->prepare($sql, $arguments);
        $result = $this->link->query($prepared_sql);

        $this->logQuery($prepared_sql, microtime(true) - $microtime);

        return $result;
    }

    /**
     * Log query.
     *
     * @param string $sql
     * @param float  $execution_time
     */
    private function logQuery($sql, $execution_time): void
    {
        if ($sql === 'BEGIN WORK' || $sql === 'COMMIT' || $sql === 'ROLLBACK') {
            return;
        }

        $this->all_queries[] = $sql;
        $this->all_queries_exec_time += $execution_time;

        AngieApplication::log()->debug(
            'Executed query in {exec_time} milisecond(s)',
            [
                'query' => $this->trimQueryLenghtForLogging($sql),
                'exec_time' => ceil($execution_time * 1000),
            ]
        );
    }

    private function trimQueryLenghtForLogging(string $sql): string
    {
        return strlen_utf($sql) > 10000 ? substr_utf($sql, 0, 10000) : $sql;
    }

    /**
     * @var array
     */
    private $all_queries = [];

    /**
     * @var float
     */
    private $all_queries_exec_time = 0.0;

    public function getAllQueries()
    {
        return $this->all_queries;
    }

    public function getQueryCount()
    {
        return count($this->all_queries);
    }

    public function getAllQueriesExecTime()
    {
        return round($this->all_queries_exec_time, 5);
    }

    /**
     * Try to survive MySQL has gone away errors.
     *
     * @param  string             $sql
     * @param  array              $arguments
     * @return bool|mysqli_result
     * @throws DBQueryError
     * @throws Exception
     */
    private function handleMySqlGoneAway($sql, $arguments = null)
    {
        if (defined('DB_AUTO_RECONNECT') && DB_AUTO_RECONNECT > 0) {
            for ($i = 1; $i <= DB_AUTO_RECONNECT; ++$i) {
                try {
                    $this->reconnect();
                    $query_result = $this->executeQuery($sql, $arguments);
                    if ($query_result !== false) {
                        return $query_result;
                    }
                } catch (Exception $e) {
                    throw $e; // rethrow exception
                }
            }
        }

        // Not executed after reconnects?
        throw new DBQueryError($sql, $this->link->errno, $this->link->error);
    }

    /**
     * Reopen connection, in case that connection has been lost.
     *
     * @throws DBReconnectError
     */
    public function reconnect()
    {
        if ($this->connection_parameters && is_foreachable($this->connection_parameters)) {
            $this->connect($this->connection_parameters);
        } else {
            throw new DBReconnectError('Connection parameters not found');
        }
    }

    /**
     * Try to survive deadlock.
     *
     * @param  string             $sql
     * @param  array|null         $arguments
     * @return bool|mysqli_result
     * @throws DBQueryError
     */
    private function handleDeadlock($sql, $arguments = null)
    {
        if (defined('DB_DEADLOCK_RETRIES') && DB_DEADLOCK_RETRIES) {
            for ($i = 1; $i <= DB_DEADLOCK_RETRIES; ++$i) {
                // Seconds to miliseconds, and sleep
                usleep(DB_DEADLOCK_SLEEP * 1000000);

                $query_result = $this->executeQuery($sql, $arguments);
                if ($query_result !== false) {
                    return $query_result;
                }
            }
        }

        // Not executed after retries?
        throw new DBQueryError($sql, $this->link->errno, $this->link->error);
    }

    /**
     * Disconnect.
     *
     * Note: If transaction is left open, it will be closed.
     */
    public function disconnect()
    {
        if ($this->link instanceof mysqli) {
            if ($this->transaction_level) {
                $this->rollback();
            }

            $this->link->close();
        }
    }

    /**
     * Return the resource.
     *
     * @return MySQLi
     */
    public function &getLink()
    {
        return $this->link;
    }

    /**
     * Return number of affected rows.
     *
     * @return int
     */
    public function affectedRows()
    {
        return $this->link->affected_rows;
    }

    /**
     * Return last insert ID.
     *
     * @return int
     */
    public function lastInsertId()
    {
        return $this->link->insert_id;
    }

    /**
     * Begin transaction.
     *
     * @param string $message
     */
    public function beginWork($message = null)
    {
        if ($this->transaction_level === 0) {
            $this->execute('BEGIN WORK');
        }
        ++$this->transaction_level;

        AngieApplication::log()->debug(
            'Transaction open at level {level}',
            [
                'level' => $this->transaction_level,
            ]
        );
    }

    /**
     * Commit transaction.
     *
     * @param string $message
     */
    public function commit($message = null)
    {
        if (empty($this->transaction_level)) {
            throw new LogicException('Only open transactions can be committed');
        }

        $current_transaction_level = $this->transaction_level;

        --$this->transaction_level;

        if ($this->transaction_level === 0) {
            $this->execute('COMMIT');
        }

        AngieApplication::log()->debug(
            'Transaction at level {level} committed',
            [
                'level' => $current_transaction_level,
            ]
        );
    }

    /**
     * Rollback transaction.
     *
     * @param string $message
     */
    public function rollback($message = null)
    {
        if ($this->transaction_level) {
            $this->execute('ROLLBACK');

            AngieApplication::log()->notice(
                'Transaction at level {level} rolled back',
                [
                    'level' => $this->transaction_level,
                ]
            );

            $this->transaction_level = 0;
        }
    }

    /**
     * Return true if system is in transaction.
     *
     * @return bool
     */
    public function inTransaction()
    {
        return $this->transaction_level > 0;
    }

    /**
     * Escape string before we use it in query...
     *
     * @param  string            $unescaped String that need to be escaped
     * @return string
     * @throws InvalidParamError
     */
    public function escape($unescaped)
    {
        // Date time value
        if ($unescaped instanceof DateTimeValue) {
            return "'" . $this->link->real_escape_string(date(DATETIME_MYSQL, $unescaped->getTimestamp())) . "'";

            // Date value
        } elseif ($unescaped instanceof DateValue) {
            return "'" . $this->link->real_escape_string(date(DATE_MYSQL, $unescaped->getTimestamp())) . "'";

            // Float
        } elseif (is_float($unescaped)) {
            return "'" . str_replace(',', '.', (float) $unescaped) . "'"; // replace , with . for locales where comma is used by the system (German for example)

            // Boolean (maps to TINYINT(1))
        } elseif (is_bool($unescaped)) {
            return $unescaped ? "'1'" : "'0'";

            // NULL
        } elseif (is_null($unescaped)) {
            return 'NULL';

            // Escape first cell of each row
        } elseif ($unescaped instanceof DBResult) {
            $escaped = [];
            foreach ($unescaped as $v) {
                $escaped[] = $this->escape(array_shift($v));
            }

            return implode(', ', $escaped);

            // Escape each array element
        } elseif (is_array($unescaped)) {
            $escaped = [];
            foreach ($unescaped as $v) {
                $escaped[] = $this->escape($v);
            }

            return implode(', ', $escaped);

            // Regular string and integer escape
        } else {
            if (!is_scalar($unescaped)) {
                throw new InvalidParamError('unescaped', $unescaped, '$unescaped is expected to be scalar, array, or instance of: DateValue, DateTimeValue, DBResult');
            }

            return "'" . $this->link->real_escape_string($unescaped) . "'";
        }
    }

    // ---------------------------------------------------
    //  Table management
    // ---------------------------------------------------

    /**
     * Escape table field name.
     *
     * @param  string $unescaped
     * @return string
     */
    public function escapeFieldName($unescaped)
    {
        return "`$unescaped`";
    }

    /**
     * Returns true if table $name exists.
     *
     * @param  string $name
     * @return bool
     */
    public function tableExists($name)
    {
        if ($name) {
            $result = $this->execute('SHOW TABLES LIKE ?', [$name]);

            return $result instanceof DBResult && $result->count() == 1;
        }

        return false;
    }

    /**
     * Create new table instance.
     *
     * @param  string       $name
     * @return MySQLDBTable
     */
    public function createTable($name)
    {
        return new MySQLDBTable($name);
    }

    /**
     * Load table information.
     *
     * @param  bool         $name
     * @return MySQLDBTable
     */
    public function loadTable($name)
    {
        return new MySQLDBTable($name, true);
    }

    /**
     * List names of the table.
     *
     * @param  string $table_name
     * @return array
     */
    public function listTableFields($table_name)
    {
        $rows = $this->execute("DESCRIBE $table_name");
        if (is_foreachable($rows)) {
            $result = [];
            foreach ($rows as $row) {
                $result[] = $row['Field'];
            }

            return $result;
        }

        return [];
    }

    /**
     * Drop list of tables.
     *
     * @param  array        $tables
     * @param  string       $prefix
     * @throws DBQueryError
     */
    public function dropTables($tables, $prefix = '')
    {
        if (!empty($tables)) {
            $tables = (array) $tables;

            foreach ($tables as $k => $v) {
                $tables[$k] = $this->escapeTableName($prefix . $v);
            }

            $this->execute('DROP TABLES ' . implode(', ', $tables));
        }
    }

    /**
     * Escape table name.
     *
     * @param  string $unescaped
     * @return string
     */
    public function escapeTableName($unescaped)
    {
        return "`$unescaped`";
    }

    /**
     * Return array of table indexes.
     *
     * @param  string $table_name
     * @return array
     */
    public function listTableIndexes($table_name)
    {
        $rows = $this->execute("SHOW INDEXES FROM $table_name");
        if (is_foreachable($rows)) {
            $result = [];
            foreach ($rows as $row) {
                $key_name = $row['Key_name'];

                if (!in_array($key_name, $result)) {
                    $result[] = $key_name;
                }
            }

            return $result;
        }

        return [];
    }

    /**
     * Drop all tables from database.
     *
     * @return bool
     */
    public function clearDatabase()
    {
        $tables = $this->listTables();
        if (is_foreachable($tables)) {
            return $this->execute('DROP TABLES ' . implode(', ', $tables));
        } else {
            return true; // it's already clear
        }
    }

    // ---------------------------------------------------
    //  File import / export
    // ---------------------------------------------------

    /**
     * Return array of tables from selected database.
     *
     * If there is no tables in database empty array is returned
     *
     * @param  string $prefix
     * @param  bool   $include_views
     * @return array
     */
    public function listTables($prefix = null, $include_views = false)
    {
        if ($prefix) {
            $rows = $this->execute("SHOW FULL TABLES LIKE '$prefix%'");
        } else {
            $rows = $this->execute('SHOW FULL TABLES');
        }

        $tables = [];

        if ($rows && is_foreachable($rows)) {
            foreach ($rows as $row) {
                $table_name = array_shift($row);

                if ($include_views || $row['Table_type'] == 'BASE TABLE') {
                    $tables[] = $table_name;
                }
            }
        }

        return count($tables) ? $tables : null;
    }

    /**
     * Gets maximum packet size allowed to be inserted into database.
     *
     * @return int
     */
    public function getMaxPacketSize()
    {
        return intval($this->getServerVariable('max_allowed_packet'));
    }

    /**
     * Do a mysql dump of specified tables.
     *
     * If $table_name is empty it will dump all tables in current database
     *
     * @param  array  $tables
     * @param  string $output_file
     * @param  bool   $dump_structure
     * @param  bool   $dump_data
     * @throws Error
     */
    public function exportToFile($tables, $output_file, $dump_structure = true, $dump_data = true)
    {
        $max_query_length = 838860; // maximum query length

        if (empty($tables)) {
            $tables = $this->listTables();
        }

        if (is_foreachable($tables)) {
            $handle = fopen($output_file, 'w');

            if (empty($handle)) {
                throw new Error("Cannot create output file: '$output_file'");
            }

            foreach ($tables as $table_name) {
                // Dump_structure
                if ($dump_structure) {
                    $create_table = $this->executeFirstRow("SHOW CREATE TABLE $table_name");
                    fwrite($handle, "DROP TABLE IF EXISTS $table_name;\n" . $create_table['Create Table'] . ";\n\n");
                }

                // Dump_data
                if ($dump_data) {
                    fwrite($handle, "/*!40000 ALTER TABLE $table_name DISABLE KEYS */;\n");

                    $query_result = $this->link->query("SELECT * FROM $table_name");

                    $inserted_values = '';
                    while ($row = $query_result->fetch_array(MYSQLI_NUM)) {
                        $values = '';

                        foreach ($row as $field) {
                            if ($values) {
                                $values .= ',';
                            }

                            $values .= $field === null ? 'NULL' : "'" . $this->link->real_escape_string($field) . "'";
                        }

                        $inserted_values .= ($inserted_values ? ',' : '');
                        $inserted_values .= '(' . $values . ')';

                        if (strlen($inserted_values) > $max_query_length) {
                            fwrite($handle, "INSERT INTO $table_name VALUES $inserted_values;\n");
                            $inserted_values = '';
                        }
                    }

                    if ($inserted_values) {
                        fwrite($handle, "INSERT INTO $table_name VALUES $inserted_values;\n");
                    }
                    fwrite($handle, "/*!40000 ALTER TABLE $table_name ENABLE KEYS */;\n");
                }
            }

            fclose($handle);
        }
    }

    /**
     * Returns true if server we are connected to supports collation.
     *
     * @return bool
     */
    public function supportsCollation()
    {
        return version_compare($this->getServerVersion(), '4.1') >= 0;
    }

    /**
     * Return version of the server.
     *
     * @return string
     */
    public function getServerVersion()
    {
        return $this->link->get_server_info();
    }

    /**
     * Return true if we have InnoDB support.
     *
     * @return bool
     */
    public function hasInnoDBSupport()
    {
        $engines = DB::execute('SHOW ENGINES');

        if ($engines) {
            foreach ($engines as $engine) {
                if (strtolower($engine['Engine']) == 'innodb' && in_array(strtolower($engine['Support']), ['yes', 'default'])) {
                    return true;
                }
            }
        }

        return false;
    }
}
