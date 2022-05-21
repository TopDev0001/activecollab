<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\NamedList;

/**
 * Database table manager tool.
 *
 * @package angie.library.database
 */
abstract class DBTable
{
    private string $name;
    private bool $new = true;

    /**
     * @var NamedList|DBColumn[]
     */
    private NamedList $columns;

    /**
     * @var NamedList|DBIndex[]
     */
    private NamedList $indices;

    private array $type_map = [
        DBBinaryColumn::class => [
            'tinyblob' => DBColumn::TINY,
            'blob' => DBColumn::NORMAL,
            'mediumblob' => DBColumn::MEDIUM,
            'longblob' => DBColumn::BIG,
        ],

        DBBoolColumn::class => [
            'bool' => null,
            'boolean' => null,
        ],

        DBDateColumn::class => [
            'date' => null,
        ],

        DBDateTimeColumn::class => [
            'timestamp' => null,
            'datetime' => null,
        ],

        DBEnumColumn::class => [
            'enum' => null,
        ],

        DBFloatColumn::class => [
            'float' => null,
            'double' => null,
            'real' => null,
        ],

        DBDecimalColumn::class => [
            'decimal' => null,
            'numeric' => null,
        ],

        DBIntegerColumn::class => [
            'tinyint' => DBColumn::TINY,
            'smallint' => DBColumn::SMALL,
            'mediumint' => DBColumn::MEDIUM,
            'int' => DBColumn::NORMAL,
            'bigint' => DBColumn::BIG,
        ],

        DBSetColumn::class => [
            'set' => null,
        ],

        DBStringColumn::class => [
            'varchar' => null,
        ],

        DBTextColumn::class => [
            'tinytext' => DBColumn::TINY,
            'text' => DBColumn::NORMAL,
            'mediumtext' => DBColumn::MEDIUM,
            'longtext' => DBColumn::BIG,
        ],

        DBTimeColumn::class => [
            'time' => null,
        ],

        DBJsonColumn::class => [
            'json' => null,
        ],
    ];
    /**
     * Model traits.
     */
    private array $model_traits = [];

    /**
     * Construct new table.
     *
     * If $load is set to true instance will load table informatin from database
     */
    public function __construct(string $name, bool $load = false)
    {
        $this->columns = new NamedList();
        $this->indices = new NamedList();

        $this->name = $name;

        if ($load) {
            $this->load();
        }
    }

    // ---------------------------------------------------
    //  CRUD
    // ---------------------------------------------------

    /**
     * Load table data from database.
     */
    public function load(string $table_prefix = null): void
    {
        $column_rows = DB::execute('SHOW COLUMNS FROM ' . DB::escapeTableName($table_prefix . $this->name));

        if (!empty($column_rows)) {
            foreach ($column_rows as $column_row) {
                $column = $this->typeStringToColumn($column_row['Field'], $column_row['Type']);
                $column->loadFromRow($column_row);

                $this->getColumns()->add($column->getName(), $column);
            }

            $index_rows = DB::execute('SHOW INDEX FROM ' . $this->name);
            if (!empty($index_rows)) {
                foreach ($index_rows as $index_row) {
                    $name = $index_row['Key_name'];

                    if ($this->getIndices()->get($name) instanceof DBIndex) {
                        $this->getIndices()->get($name)->addColumn($index_row['Column_name']); // Key on multiple columns
                    } else {
                        $index = new DBIndex($name, DBIndex::KEY, false);
                        $index->loadFromRow($index_row);
                        $index->setTable($this);

                        $this->getIndices()->add($index->getName(), $index);
                    }
                }
            }

            $this->new = false;
        }
    }

    /**
     * Analyze type string and return proper column instance.
     *
     * @return DBColumn
     */
    private function typeStringToColumn(string $name, string $string)
    {
        $string = str_replace('tinyint(1)', 'bool', $string); // alias!

        $parts = explode(' ', $string);

        $first_part = $parts[0] ?? '';

        $open_bracket = strpos($first_part, '(');
        $close_bracket = strpos($first_part, ')');

        if ($open_bracket !== false && $close_bracket !== false) {
            $type_name = substr($first_part, 0, $open_bracket);
            $additional = explode(',', substr($first_part, $open_bracket + 1, $close_bracket - $open_bracket - 1));
            if (is_foreachable($additional)) {
                foreach ($additional as $k => $v) {
                    $additional[$k] = trim($v, "'");
                }
            }
        } else {
            $type_name = $first_part;
            $additional = null;
        }

        $type_class = '';
        $type_size = null;

        foreach ($this->type_map as $class_name => $variations) {
            foreach ($variations as $k => $v) {
                if ($k == $type_name) {
                    $type_class = $class_name;
                    if ($v !== null) {
                        $type_size = $v;
                    }

                    break 2;
                }
            }
        }

        if (!class_exists($type_class, false)) {
            $expected_column_type = sprintf('%s/columns/%s.class.php', __DIR__, $type_class);

            if (is_file($expected_column_type)) {
                require_once $expected_column_type;
            }
        }

        $column = new $type_class($name);

        if ($column instanceof DBColumn) {
            $column->setTable($this);
            if ($additional) {
                $column->processAdditional($additional);
            }
            if ($type_size !== null) {
                $column->setSize($type_size);
            }
        }

        return $column;
    }

    /**
     * Provide interface to columns property.
     *
     * @return NamedList
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Provide interface to indices property.
     *
     * @return NamedList|DBIndex[]
     */
    public function getIndices()
    {
        return $this->indices;
    }

    /**
     * Create new table instance.
     *
     * This is a stub method, that needs to be overriden in classes that inherit
     * DBTable
     *
     * @param string $name
     * @param bool   $load
     */
    public static function create($name, $load = false)
    {
        throw new NotImplementedError(__METHOD__);
    }

    // ---------------------------------------------------
    //  Columns
    // ---------------------------------------------------

    /**
     * Save new table into the database via provided connection.
     *
     * @param string $table_prefix
     */
    public function save(string $table_prefix = null)
    {
        DB::execute($this->getCreateCommand($table_prefix));
        $this->new = false;
    }

    /**
     * Return CREATE TABLE command code.
     */
    public function getCreateCommand(string $table_prefix = null): string
    {
        $column_definitions = [];
        foreach ($this->columns as $column) {
            $column_definitions[] = '  ' . $column->prepareDefinition();
        }

        $index_definitions = [];
        foreach ($this->indices as $index) {
            $index_definitions[] = '  ' . $index->prepareDefinition();
        }

        $options = [];

        foreach ($this->getOptions() as $k => $v) {
            $options[] = "$k=$v";
        }

        $table_name = DB::escapeTableName("{$table_prefix}{$this->name}");

        $command = "CREATE TABLE $table_name (\n";
        $command .= implode(",\n", $column_definitions);
        if (is_foreachable($index_definitions)) {
            $command .= ",\n" . implode(",\n", $index_definitions);
        }
        $command .= "\n) " . implode(' ', $options) . ';';

        return $command;
    }

    /**
     * Return array of table options.
     *
     * @return array
     */
    abstract public function getOptions();

    public function truncate(string $table_prefix = null): void
    {
        if ($this->exists($table_prefix)) {
            DB::execute(
                sprintf('TRUNCATE TABLE %s', DB::escapeTableName($table_prefix . $this->name))
            );
        } else {
            throw new InvalidParamError('name', $this->name, "Table '$this->name' does not exist");
        }
    }

    /**
     * Drop table from database.
     */
    public function delete(string $table_prefix = null): void
    {
        if ($this->exists($table_prefix)) {
            DB::execute(sprintf('DROP TABLE %s', DB::escapeTableName($table_prefix . $this->name)));
            $this->new = true;
        } else {
            throw new InvalidParamError('name', $this->name, "Table '$this->name' does not exist");
        }
    }

    /**
     * Check if table with this name exists in database.
     *
     * @param  string $table_prefix
     * @return bool
     */
    public function exists($table_prefix = null)
    {
        $info = DB::execute('SHOW TABLES LIKE ?', $table_prefix . $this->name);

        return $info instanceof DBResult && $info->count() > 0;
    }

    /**
     * Add array of columns.
     *
     * @param  array   $columns
     * @return DBTable
     */
    public function &addColumns($columns)
    {
        foreach ($columns as &$column) {
            $this->addColumn($column);
        }

        return $this;
    }

    // ---------------------------------------------------
    //  Indices
    // ---------------------------------------------------

    /**
     * Add column to the list of columns.
     *
     * @param  DBColumn|DBCompositeColumn $column
     * @return DBColumn
     */
    public function addColumn($column, string $first_or_after_column_name = null)
    {
        // Add single column to the table
        if ($column instanceof DBColumn) {
            $column->setTable($this);

            if ($this->isLoaded()) {
                $after = '';

                if ($first_or_after_column_name === true) {
                    $after = ' FIRST';
                } elseif ($first_or_after_column_name && $this->getColumn($first_or_after_column_name) instanceof DBColumn) {
                    $after = " AFTER $first_or_after_column_name";
                }

                //$after = $first_or_after_column_name && $this->getColumn($first_or_after_column_name) instanceof DBColumn ? " AFTER $first_or_after_column_name " : '';

                if ($column instanceof DBIntegerColumn && $column->getAutoIncrement()) {
                    $prepared_definition = str_replace('auto_increment', '', $column->prepareDefinition());

                    // Add field without auto - increment flag
                    DB::execute("ALTER TABLE $this->name ADD $prepared_definition $after");

                    if ($this->hasPrimaryKey()) {
                        $this->addIndex(new DBIndex($column->getName(), DBIndex::PRIMARY, $column->getName()));
                    } else {
                        $this->addIndex(new DBIndexPrimary($column->getName()));
                    }

                    // Now that we have a key we can set auto_increment flag
                    DB::execute("ALTER TABLE $this->name CHANGE " . $column->getName() . ' ' . $column->prepareDefinition() . ' ' . $after);
                } else {
                    DB::execute("ALTER TABLE $this->name ADD " . $column->prepareDefinition() . ' ' . $after);
                }
            } else {
                if ($column instanceof DBIntegerColumn && $column->getAutoIncrement()) {
                    if ($this->hasPrimaryKey()) {
                        $this->addIndex(new DBIndex($column->getName(), DBIndex::PRIMARY, $column->getName()));
                    } else {
                        $this->addIndex(new DBIndexPrimary($column->getName()));
                    }
                }
            }

            // Add and trigger added event
            $this->columns->add($column->getName(), $column);
            $column->addedToTable();

            // Add composite column to the table
        } elseif ($column instanceof DBCompositeColumn) {
            $column->setTable($this);

            $after = $first_or_after_column_name;

            foreach ($column->getColumns() as $c) {
                $this->addColumn($c, $after);
                $after = $c->getName();
            }

            $column->addedToTable();
        } else {
            throw new InvalidInstanceError('column', $column, ['DBColumn', 'DBCompositeColumn']);
        }

        return $column;
    }

    /**
     * Returns true if this table exists and is loaded.
     *
     * @return bool
     */
    public function isLoaded()
    {
        return !$this->new;
    }

    /**
     * Return column by name.
     *
     * @param  string   $name
     * @return DBColumn
     */
    public function getColumn($name)
    {
        return isset($this->columns[$name]) ? $this->columns[$name] : null;
    }

    /**
     * Returns true if this table has primary key.
     *
     * @return bool
     */
    public function hasPrimaryKey()
    {
        foreach ($this->getIndices() as $index) {
            if ($index->getType() == DBIndex::PRIMARY) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add index to table definition.
     */
    public function addIndex(DBIndex $index)
    {
        if ($this->isLoaded()) {
            DB::execute("ALTER TABLE $this->name ADD " . $index->prepareDefinition());
        }

        $index->setTable($this);
        $this->getIndices()->add($index->getName(), $index);
    }

    /**
     * Alter existing column.
     *
     * @param  string   $name
     * @param  bool     $after
     * @return DBColumn
     */
    public function alterColumn($name, DBColumn $new_definition, $after = null)
    {
        if ($this->getColumn($name) instanceof DBColumn) {
            if (!($new_definition->getTable() instanceof self) || ($new_definition->getTable()->getName() != $this->name)) {
                $new_definition->setTable($this);
            }

            if ($new_definition instanceof DBIntegerColumn && $new_definition->getAutoIncrement()) {
                $key_exists = false;
                foreach ($this->getIndices() as $index) {
                    if (in_array($new_definition->getName(), $index->getColumns())) {
                        $key_exists = true;

                        break;
                    }
                }

                if (!$key_exists) {
                    if ($this->hasPrimaryKey()) {
                        $this->addIndex(new DBIndex($new_definition->getName(), DBIndex::PRIMARY, $new_definition->getName()));
                    } else {
                        $this->addIndex(new DBIndexPrimary($new_definition->getName()));
                    }
                }
            }

            if ($after) {
                $after = "AFTER $after";
            }

            DB::execute("ALTER TABLE $this->name CHANGE $name " . $new_definition->prepareDefinition() . ' ' . $after);
            $this->columns[$name] = $new_definition;
        } else {
            throw new InvalidParamError('name', $name, "Column '$name' does not exist");
        }

        return $this->getColumn($name);
    }

    /**
     * Return name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name.
     *
     * @param  string  $value
     * @return DBTable
     */
    public function &setName($value)
    {
        $this->name = $value;

        return $this;
    }

    /**
     * Drop specific column.
     *
     * @param string $name
     */
    public function dropColumn($name)
    {
        if ($this->getColumn($name) instanceof DBColumn) {
            DB::execute("ALTER TABLE $this->name DROP $name");
        } else {
            throw new InvalidParamError('name', $name, "Column '$name' does not exist");
        }
    }

    // ---------------------------------------------------
    //  Options
    // ---------------------------------------------------

    /**
     * Return true if given index exists.
     *
     * @param  string $name
     * @return bool
     */
    public function indexExists($name)
    {
        return isset($this->indices[$name]) && $this->indices[$name] instanceof DBIndex;
    }

    // ---------------------------------------------------
    //  Flags
    // ---------------------------------------------------

    /**
     * Add indices.
     *
     * @param  array   $indices
     * @return DBTable
     */
    public function &addIndices($indices)
    {
        foreach ($indices as &$index) {
            $this->addIndex($index);
        }

        return $this;
    }

    /**
     * Alter existing index.
     *
     * @param string $name
     */
    public function alterIndex($name, DBIndex $new_definition)
    {
        if ($this->getIndex($name) instanceof DBIndex) {
            DB::execute("ALTER TABLE $this->name DROP INDEX $name, ADD " . $new_definition->prepareDefinition());
            $this->indices[$name] = $new_definition;
        } else {
            throw new InvalidParamError('name', $name, "Index '$name' does not exist");
        }
    }

    // ---------------------------------------------------
    //  Util
    // ---------------------------------------------------

    /**
     * Return single index.
     *
     * @param  string  $name
     * @return DBIndex
     */
    public function getIndex($name)
    {
        return isset($this->indices[$name]) ? $this->indices[$name] : null;
    }

    /**
     * Drop index by name.
     *
     * @param string $name
     */
    public function dropIndex($name)
    {
        if ($this->getIndex($name) instanceof DBIndex) {
            DB::execute("ALTER TABLE $this->name DROP INDEX `$name`");
            unset($this->indices[$name]);
        } else {
            throw new InvalidParamError('name', $name, "Index '$name' does not exist");
        }
    }

    /**
     * Drop primary key.
     */
    public function dropPrimaryKey()
    {
        if ($this->hasPrimaryKey()) {
            DB::execute("ALTER TABLE $this->name DROP PRIMARY KEY");
        }
    }

    /**
     * Returns true if this table is new, not loaded.
     *
     * @return bool
     */
    public function isNew()
    {
        return $this->new;
    }

    /**
     * Return model traits.
     *
     * @return array
     */
    public function getModelTraits()
    {
        return $this->model_traits;
    }

    // ---------------------------------------------------
    //  Getters and setters
    // ---------------------------------------------------

    /**
     * Add model trait.
     *
     * @param  string $interface
     * @param  string $implementation
     * @return $this
     */
    public function &addModelTrait($interface = null, $implementation = null)
    {
        if (is_array($interface)) {
            foreach ($interface as $k => $v) {
                $this->addModelTrait($k, $v);
            }

            // Interface and implementation (optional)
        } elseif ($interface) {
            if (empty($this->model_traits[$interface])) {
                $this->model_traits[$interface] = [];
            }

            $this->model_traits[$interface][] = $implementation;

            // Just a trait
        } elseif ($interface === null && $implementation) {
            if (empty($this->model_traits['--just-paste-trait--'])) {
                $this->model_traits['--just-paste-trait--'] = [];
            }

            $this->model_traits['--just-paste-trait--'][] = $implementation;

            // Invalid input
        } else {
            throw new InvalidParamError('interface', $interface);
        }

        return $this;
    }

    public function prepareModelDefinition(): string
    {
        $result = "DB::createTable('" . $this->getName() . "')";

        $id_added = false; // Indicator whether ID column is added
        $type_added = false; // Indicator whether type column is added

        if (is_foreachable($this->columns)) {
            $result .= "->addColumns([\n";
            foreach ($this->columns as $column) {
                $result .= '    ' . $column->prepareModelDefinition() . ", \n";

                if ($column->getName() == 'id' && $column instanceof DBIntegerColumn && $column->getAutoIncrement()) {
                    $id_added = true;
                }

                if ($column->getName() == 'type' && $column instanceof DBStringColumn) {
                    $type_added = true;
                }
            }
            $result .= '])';
        }

        if (is_foreachable($this->indices)) {
            $indices = [];

            foreach ($this->indices as $index) {
                if (($index instanceof DBIndexPrimary || $index->getType() == DBIndex::PRIMARY) && $id_added) {
                    continue; // Skip primary key if we have it added via DBIdColumn
                }

                if ($type_added && $index->getName() == 'type') {
                    continue;
                }

                $indices[] = '    ' . $index->prepareModelDefinition() . ", \n";
            }

            if (count($indices)) {
                $result .= "->addIndices([\n" . implode('', $indices) . ']);';
            } else {
                $result .= ';';
            }
        }

        return $result;
    }
}
