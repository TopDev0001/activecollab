<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Inflector;

abstract class AngieModelMigration
{
    /**
     * List of steps that need to be executed before we can execute this migration.
     *
     * @var array
     */
    private $execute_after = [];

    /**
     * Cached changeset name.
     *
     * @var bool
     */
    private $changeset = false;

    /**
     * Cached list of used tables.
     *
     * @var array
     */
    private $used_tables = [];

    /**
     * @var bool
     */
    private $config_options_have_updated_on_field;

    /**
     * Return array of migrations that need to be executed before we can execute this migration.
     *
     * @return array|null
     */
    public function getExecuteAfter()
    {
        return count($this->execute_after) ? $this->execute_after : null;
    }

    // ---------------------------------------------------
    //  Misc
    // ---------------------------------------------------

    /**
     * Make sure that this migration is executed after given list of migrations.
     */
    public function executeAfter()
    {
        if (func_num_args()) {
            foreach (func_get_args() as $migration_name) {
                $this->execute_after[] = $migration_name;
            }

            if (count($this->execute_after) > 1) {
                $this->execute_after = array_unique($this->execute_after);
            }
        }
    }

    /**
     * Upgrade the data.
     */
    abstract public function up();

    /**
     * Downgrade the data.
     */
    public function down()
    {
    }

    /**
     * Return migration description.
     *
     * @return string
     */
    public function getDescription()
    {
        return Inflector::humanize(Inflector::underscore(get_class($this)));
    }

    // ---------------------------------------------------
    //  Table locking / unlocking
    // ---------------------------------------------------

    public function canExecute(string &$reason): bool
    {
        return true;
    }

    public function getUsedTables(): array
    {
        return $this->used_tables;
    }

    public function useTableForAlter(string $name_without_prefix): DBTable
    {
        return DB::loadTable($this->useTable($name_without_prefix));
    }

    private function useTable(string $name_without_prefix): string
    {
        if (empty($this->used_tables[$name_without_prefix])) {
            $this->used_tables[$name_without_prefix] = $name_without_prefix;
        }

        return $this->used_tables[$name_without_prefix];
    }

    public function useTables(string ...$table_names): array
    {
        if (empty($table_names)) {
            throw new InvalidParamError('table_names', $table_names, 'One or more table names expected');
        }

        $used_tables = [];

        foreach ($table_names as $table_name) {
            $used_tables[] = $this->useTable($table_name);
        }

        return $used_tables;
    }

    public function doneUsingTables(): void
    {
        $this->used_tables = [];
    }

    // ---------------------------------------------------
    //  Operations
    // ---------------------------------------------------

    /**
     * Execute SQL query and return content of first column as an array.
     *
     * @param mixed
     * @return array
     */
    public function executeFirstColumn()
    {
        $arguments = func_get_args();

        if (empty($arguments)) {
            throw new InvalidParamError('arguments', $arguments, 'DB::executeFirstColumn() function requires at least SQL query to be provided');
        }

        return DB::getConnection()->executeFirstColumn(array_shift($arguments), $arguments);
    }

    /**
     * Return number of affected rows.
     *
     * @return int
     */
    public function affectedRows()
    {
        return DB::getConnection()->affectedRows();
    }

    /**
     * Return last insert ID.
     *
     * @return int
     */
    public function lastInsertId()
    {
        return DB::getConnection()->lastInsertId();
    }

    /**
     * Load table instance.
     *
     * @param  string  $name
     * @return DBTable
     */
    public function loadTable($name)
    {
        return DB::loadTable($name);
    }

    /**
     * Upgrade the table.
     *
     * $table can be a table instance. If it is not a table instance, than it should be a table name and
     * $colums and $indexes parameters are used
     *
     * @param  string|DBTable    $table
     * @param  array|null        $columns
     * @param  array|null        $indices
     * @throws InvalidParamError
     */
    public function createTable($table, $columns = null, $indices = null)
    {
        if ($table instanceof DBTable) {
            $table->save();
        } elseif (is_string($table)) {
            $create_table = DB::createTable($table);

            $create_table->addColumns($columns);

            if ($indices) {
                $create_table->addIndices($indices);
            }

            $create_table->save();
        } else {
            throw new InvalidParamError('table', $table, 'Table is expected to be a table name or a DBTable instance');
        }
    }

    public function renameTable($table_name, $new_table_name): void
    {
        DB::execute(
            sprintf(
                'RENAME TABLE %s TO %s',
                DB::escapeTableName($table_name),
                DB::escapeTableName($new_table_name)
            )
        );
    }

    public function dropTable(string ...$table_names): void
    {
        foreach ($table_names as $table_name) {
            DB::execute(
                sprintf(
                    'DROP TABLE IF EXISTS %s',
                    DB::escapeTableName($table_name)
                )
            );
        }
    }

    /**
     * Return true if one or more modules are installed.
     *
     * @deprecated
     */
    public function isModuleInstalled(): bool
    {
        return true;
    }

    /**
     * Add module to the list of modules.
     *
     * @param string   $name
     * @param bool     $enabled
     * @param int|null $position
     * @deprecated
     */
    public function addModule($name, $enabled = true, $position = null)
    {
        if (!$this->tableExists('modules')) {
            throw new RuntimeException('modules table has been deprecated');
        }

        if ($position === null) {
            $position = DB::executeFirstCell('SELECT MAX(`position`) FROM `modules`') + 1;
        }

        DB::execute(
            'INSERT INTO `modules` (`name`, `is_enabled`, `position`) VALUES (?, ?, ?)',
            $name,
            $enabled,
            $position
        );
    }

    public function tableExists(string $name): bool
    {
        return DB::tableExists($name);
    }

    /**
     * Remove module.
     *
     * @deprecated
     */
    public function removeModule(string $name)
    {
        if (!$this->tableExists('modules')) {
            throw new RuntimeException('modules table has been deprecated');
        }

        DB::execute('DELETE FROM `modules` WHERE `name` = ?', $name);
    }

    /**
     * Return config option value.
     *
     * @param  string     $name
     * @return mixed|null
     */
    public function getConfigOptionValue($name)
    {
        $value = $this->executeFirstCell('SELECT value FROM config_options WHERE name = ?', $name);

        return $value ? unserialize($value) : null;
    }

    // ---------------------------------------------------
    //  Module management
    // ---------------------------------------------------

    public function executeFirstCell(...$arguments)
    {
        if (empty($arguments)) {
            throw new InvalidParamError('arguments', $arguments, 'DB::executeFirstCell() function requires at least SQL query to be provided');
        }

        return DB::getConnection()->executeFirstCell(array_shift($arguments), $arguments);
    }

    /**
     * Update configuration option.
     *
     * @param string $name
     * @param mixed  $value
     * @param bool   $drop_custom_value
     */
    public function setConfigOptionValue($name, $value = null, $drop_custom_value = false)
    {
        if ($this->executeFirstCell('SELECT COUNT(name) FROM config_options WHERE name = ?', $name)) {
            if ($value && $value instanceof Closure) {
                $existing_value = unserialize(DB::executeFirstCell('SELECT value FROM config_options WHERE name = ?', $name));
                $value->__invoke($existing_value);
                $value = $existing_value;
            }

            $this->execute('UPDATE config_options SET value = ? WHERE name = ?', serialize($value), $name);
        } else {
            if ($value && $value instanceof Closure) {
                $existing_value = null;
                $value->__invoke($existing_value);
                $value = $existing_value;
            }

            $this->addConfigOption($name, $value);
        }

        if ($drop_custom_value) {
            $this->execute('DELETE FROM config_option_values WHERE name = ?', $name);
        }

        if ($this->configOptionsHaveUpdatedOnField()) {
            $this->execute('UPDATE config_options SET updated_on = NOW() WHERE name = ?', $name);
        }

        AngieApplication::cache()->remove('config_options');
    }

    /**
     * Execute sql.
     *
     * @return DbResult
     */
    public function execute(string $sql, ...$arguments)
    {
        return DB::getConnection()->execute($sql, $arguments);
    }

    // ---------------------------------------------------
    //  Config Options Management
    // ---------------------------------------------------

    /**
     * Add a new configuration option value.
     *
     * @param string $name
     * @param mixed  $value
     * @param bool   $override_if_exists
     */
    public function addConfigOption($name, $value = null, $override_if_exists = true)
    {
        if ($this->executeFirstCell("SELECT COUNT(name) AS 'row_count' FROM config_options WHERE name = ?", $name)) {
            if ($override_if_exists) {
                $this->execute('UPDATE config_options SET value = ? WHERE name = ?', serialize($value), $name);
            }
        } else {
            $this->execute('INSERT INTO config_options (name, value) VALUES (?, ?)', $name, serialize($value));
        }

        if ($this->configOptionsHaveUpdatedOnField()) {
            $this->execute('UPDATE config_options SET updated_on = NOW() WHERE name = ?', $name);
        }

        AngieApplication::cache()->remove('config_options');
    }

    private function configOptionsHaveUpdatedOnField(): bool
    {
        if ($this->config_options_have_updated_on_field === null) {
            $config_options = DB::loadTable('config_options');

            $this->config_options_have_updated_on_field = $config_options->getColumn('updated_on') instanceof DBColumn;
        }

        return $this->config_options_have_updated_on_field;
    }

    public function removeConfigOption(string $name): void
    {
        $this->transact(
            function () use ($name) {
                $this->execute('DELETE FROM `config_option_values` WHERE `name` = ?', $name);
                $this->execute('DELETE FROM `config_options` WHERE `name` = ?', $name);
            }
        );

        AngieApplication::cache()->remove('config_options');
    }

    public function transact(callable $callback, string $operation = null): void
    {
        DB::transact($callback, $operation);
    }

    public function renameConfigOption(string $current_name, string $new_name): void
    {
        $this->transact(
            function () use ($current_name, $new_name) {
                $this->execute('UPDATE `config_option_values` SET `name` = ? WHERE `name` = ?', $new_name, $current_name);
                $this->execute('UPDATE `config_options` SET `name` = ? WHERE `name` = ?', $new_name, $current_name);
            }
        );

        AngieApplication::cache()->remove('config_options');
    }

    /**
     * Return information about first owner.
     *
     * This function return array with following fields: id, name, email, created_on
     */
    public function getFirstUsableOwner(): array
    {
        $users_table_fields = DB::listTableFields('users');

        if (in_array('state', $users_table_fields)) {
            $owner = $this->executeFirstRow("SELECT id, first_name, last_name, email, created_on FROM users WHERE type IN ('Administrator', 'Owner') AND state = '3' ORDER BY created_on LIMIT 0, 1");
        } elseif (in_array('is_archived', $users_table_fields) && in_array('is_trashed', $users_table_fields)) {
            $owner = $this->executeFirstRow("SELECT id, first_name, last_name, email, created_on FROM users WHERE type IN ('Administrator', 'Owner') AND is_archived = '0' AND is_trashed = '0' ORDER BY created_on LIMIT 0, 1");
        } else {
            $owner = $this->executeFirstRow("SELECT id, first_name, last_name, email, created_on FROM users WHERE type IN ('Administrator', 'Owner') ORDER BY created_on LIMIT 0, 1");
        }

        if ($owner) {
            if ($owner['first_name'] && $owner['last_name']) {
                $user_name = $owner['first_name'] . ' ' . $owner['last_name'];
            } elseif ($owner['first_name']) {
                $user_name = $owner['first_name'];
            } elseif ($owner['last_name']) {
                $user_name = $owner['last_name'];
            } else {
                $user_name = substr($owner['email'], 0, strpos($owner['email'], '@'));
            }

            return [$owner['id'], $user_name, $owner['email'], $owner['created_on']];
        }

        return [0, '', '', null]; // Not found
    }

    public function executeFirstRow(string $sql, ...$arguments): ?array
    {
        return DB::getConnection()->executeFirstRow($sql, $arguments);
    }

    // ---------------------------------------------------
    //  Misc utils
    // ---------------------------------------------------

    public function isExecuted(): bool
    {
        return (bool) DB::executeFirstCell(
            'SELECT COUNT(`id`) FROM `executed_model_migrations` WHERE `migration` = ?',
            get_class($this)
        );
    }

    /**
     * Set this migration as executed.
     */
    public function setAsExecuted(): void
    {
        $changeset = $this->getChangeset();

        $changeset_timestamp = $this->getChangesetTimestamp($changeset);
        $changeset_name = substr($changeset, 11);

        DB::execute(
            'REPLACE INTO `executed_model_migrations` (`migration`, `changeset_timestamp`, `changeset_name`, `executed_on`) VALUES (?, ?, ?, UTC_TIMESTAMP())',
            get_class($this),
            $changeset_timestamp,
            $changeset_name
        );
    }

    public function setAsNotExecuted(): void
    {
        DB::execute('DELETE FROM `executed_model_migrations` WHERE `migration` = ?', get_class($this));
    }

    // ---------------------------------------------------
    //  Execution log
    // ---------------------------------------------------

    public function getChangeset(): string
    {
        if ($this->changeset === false) {
            $reflection = new ReflectionClass($this);

            $this->changeset = basename(dirname($reflection->getFileName()));
        }

        return $this->changeset;
    }

    /**
     * Return time stamp from a given change-set name.
     *
     * @param  string       $name
     * @return string|false
     */
    public function getChangesetTimestamp($name)
    {
        $matches = [];

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})-(.*)$/', $name, $matches)) {
            return "$matches[1]-$matches[2]-$matches[3]";
        }

        return false;
    }
}
