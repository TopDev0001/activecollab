<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class DBIndex
{
    const PRIMARY = 0;
    const UNIQUE = 1;
    const KEY = 2;
    const FULLTEXT = 3;

    protected string $name;
    protected array $columns = [];
    protected int $type = self::KEY;
    protected ?DBTable $table = null;

    /**
     * Construct DBIndex.
     *
     * If $columns is NULL, system will create index for the field that has the
     * same name as the index
     *
     * @param mixed $columns
     */
    public function __construct(string $name, int $type = self::KEY, $columns = null)
    {
        $this->name = $name;
        $this->type = $type;

        if ($name == 'PRIMARY') {
            $this->type = self::PRIMARY;
        }

        // Use column name
        if ($columns === null) {
            $this->addColumn($name);

        // Columns are specified
        } elseif ($columns) {
            $columns = is_array($columns) ? $columns : [$columns];
            foreach ($columns as $column) {
                if ($column instanceof DBColumn) {
                    $this->addColumn($column->getName());
                } else {
                    $this->addColumn($column);
                }
            }
        }
    }

    public function addColumn(string $column_name): void
    {
        $this->columns[] = $column_name;
    }

    /**
     * Create and return new index instance.
     *
     * @param  string  $name
     * @param  int     $type
     * @param  mixed   $columns
     * @return DBIndex
     */
    public static function create($name, $type = self::KEY, $columns = null)
    {
        return new self($name, $type, $columns);
    }

    public function loadFromRow(array $row): void
    {
        $this->columns[] = $row['Column_name'];

        if ($this->name == 'PRIMARY') {
            $this->type = self::PRIMARY;
        } elseif ($row['Index_type'] == 'FULLTEXT') {
            $this->type = self::FULLTEXT;
        } elseif (!(bool) $row['Non_unique']) {
            $this->type = self::UNIQUE;
        } else {
            $this->type = self::KEY;
        }
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function prepareDefinition(): string
    {
        switch ($this->type) {
            case self::PRIMARY:
                $result = 'PRIMARY KEY';
                break;
            case self::UNIQUE:
                $result = 'UNIQUE ' . DB::escapeFieldName($this->name);
                break;
            case self::FULLTEXT:
                $result = 'FULLTEXT ' . DB::escapeFieldName($this->name);
                break;
            default:
                $result = 'INDEX ' . DB::escapeFieldName($this->name);
                break;
        }

        $column_names = [];
        foreach ($this->columns as $column) {
            $column_names[] = DB::escapeFieldName($column);
        }

        return $result . ' (' . implode(', ', $column_names) . ')';
    }

    public function prepareModelDefinition(): string
    {
        if (count($this->columns) == 1) {
            $columns = var_export($this->columns[0], true);
        } else {
            $columns = [];
            foreach ($this->columns as $k => $v) {
                $columns[] = var_export($v, true);
            }
            $columns = 'array(' . implode(', ', $columns) . ')';
        }

        // Primary key
        if ($this->type == self::PRIMARY) {
            return "DBIndexPrimary::create($columns)";

            // Index where the name of the index is the same as the column
        } elseif ($this->type == self::KEY && count($this->columns) == 1 && $this->getName() == $this->columns[0]) {
            return "DBIndex::create('" . $this->getName() . "')";

            // Everything else
        } else {
            switch ($this->type) {
                case self::UNIQUE:
                    $type = 'DBIndex::UNIQUE';
                    break;
                case self::FULLTEXT:
                    $type = 'DBIndex::FULLTEXT';
                    break;
                default:
                    $type = 'DBIndex::KEY';
                    break;
            }

            return "DBIndex::create('" . $this->getName() . "', $type, $columns)";
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $value): DBIndex
    {
        $this->name = $value;

        return $this;
    }

    public function isPrimary(): bool
    {
        return $this->type == self::PRIMARY;
    }

    public function isUnique(): bool
    {
        return ($this->type == self::PRIMARY) || ($this->type == self::UNIQUE);
    }

    public function isFulltext(): bool
    {
        return $this->type == self::FULLTEXT;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function settype(int $value): DBIndex
    {
        $this->type = $value;

        return $this;
    }

    public function getTable(): ?DBTable
    {
        return $this->table;
    }

    public function setTable(DBTable $value): DBIndex
    {
        $this->table = $value;

        return $this;
    }
}
