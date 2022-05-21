<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class DBColumn
{
    const TINY = 'tiny';
    const SMALL = 'small';
    const NORMAL = 'normal';
    const MEDIUM = 'medium';
    const BIG = 'big';

    protected string $name;

    /**
     * Default value.
     *
     * @var mixed
     */
    protected $default = null;

    /**
     * Field comment.
     *
     * @var string
     */
    protected $comment = null;

    /**
     * Field size, if field has it.
     *
     * @var string
     */
    protected $size = self::NORMAL;

    /**
     * True for fields that have size (TINY, SMALL, NORMAL, MEDIUM, BIG).
     */
    protected bool $has_size = false;

    /**
     * Indicates whether this column can have default value or not.
     */
    protected bool $has_default = true;

    /**
     * Parent table.
     *
     * @var DBTable
     */
    protected $table;

    /**
     * Construct database column.
     *
     * @param mixed $default
     */
    public function __construct(string $name, $default = null)
    {
        $this->name = $name;
        $this->default = $default;
    }

    public function loadFromRow(array $row): void
    {
        $this->default = $row['Null'] == 'NO' && $row['Default'] !== null ? $row['Default'] : null;
    }

    /**
     * Process additional parameters like VARCHAR(LENGHT), INT(10) or FLOAT(4,2).
     */
    public function processAdditional(array $additional): void
    {
    }

    public function prepareDefinition(): string
    {
        $result = DB::escapeFieldName($this->name) . ' ' . $this->prepareTypeDefinition() . ' ' . $this->prepareNull();

        if ($this->has_default && $this->prepareDefault() !== '') {
            $result .= ' DEFAULT ' . $this->prepareDefault();
        }

        return $result;
    }

    abstract public function prepareTypeDefinition(): string;

    /**
     * Prepare null / not null part of the definition.
     *
     * @return string
     */
    protected function prepareNull()
    {
        return $this->default === null ? '' : 'NOT NULL';
    }

    public function prepareDefault(): string
    {
        if ($this->default === null) {
            return 'NULL';
        } elseif ($this->default === 0) {
            return '0';
        } elseif ($this->default === '') {
            return "''";
        } else {
            return DB::escape($this->default);
        }
    }

    public function prepareModelDefinition(): string
    {
        $default = $this->getDefault() === null ? '' : ', ' . var_export($this->getDefault(), true);

        $result = get_class($this) . "::create('" . $this->getName() . "'$default)";

        if ($this->has_size && $this->getSize() != self::NORMAL) {
            switch ($this->getSize()) {
                case self::TINY:
                    $result .= '->setSize(DBColumn::TINY)';
                    break;
                case self::SMALL:
                    $result .= '->setSize(DBColumn::SMALL)';
                    break;
                case self::MEDIUM:
                    $result .= '->setSize(DBColumn::MEDIUM)';
                    break;
                case self::BIG:
                    $result .= '->setSize(DBColumn::BIG)';
                    break;
            }
        }

        return $result;
    }

    /**
     * Return default.
     *
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set default.
     *
     * @param  mixed    $value
     * @return DBColumn
     */
    public function &setDefault($value)
    {
        $this->default = $value;

        return $this;
    }

    // ---------------------------------------------------
    //  Model generator
    // ---------------------------------------------------

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
     * @param  string   $value
     * @return DBColumn
     */
    public function &setName($value)
    {
        $this->name = $value;

        return $this;
    }

    // ---------------------------------------------------
    //  Getters and setters
    // ---------------------------------------------------

    /**
     * Return size.
     *
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set size.
     *
     * @param  string   $value
     * @return DBColumn
     */
    public function &setSize($value)
    {
        $this->size = $value;

        return $this;
    }

    /**
     * Check if this column belogs to an index.
     *
     * @return bool
     */
    public function isPrimaryKey()
    {
        foreach ($this->table->getIndices() as $index) {
            if (in_array($this->name, $index->getColumns()) && $index->isPrimary()) {
                return true;
            }
        }

        return false;
    }

    public function addedToTable(): void
    {
    }

    public function getPhpType(): string
    {
        return 'string';
    }

    public function getCastingCode(): string
    {
        return '(string) $value';
    }

    /**
     * Return table.
     *
     * @return DBTable
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Set table.
     *
     * @return DBColumn
     */
    public function &setTable(DBTable $value)
    {
        $this->table = $value;

        return $this;
    }
}
