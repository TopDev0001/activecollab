<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class DBIntegerColumn extends DBNumericColumn
{
    private bool $auto_increment = false;

    /**
     * Construct numeric column.
     *
     * @param int|mixed|string|null $lenght
     * @param mixed                 $default
     */
    public function __construct(string $name, $lenght = DBColumn::NORMAL, $default = null)
    {
        if ($default !== null) {
            $default = (int) $default;
        }

        parent::__construct($name, $lenght, $default);
    }

    /**
     * Create new integer column instance.
     *
     * @param  string          $name
     * @param  int             $lenght
     * @param  mixed           $default
     * @return DBIntegerColumn
     */
    public static function create($name, $lenght = 5, $default = null)
    {
        return new self($name, $lenght, $default);
    }

    public function loadFromRow(array $row): void
    {
        parent::loadFromRow($row);
        $this->auto_increment = isset($row['Extra']) && $row['Extra'] == 'auto_increment';
    }

    public function prepareDefinition(): string
    {
        return $this->auto_increment ? parent::prepareDefinition() . ' auto_increment' : parent::prepareDefinition();
    }

    public function prepareTypeDefinition(): string
    {
        $result = $this->length ? "int($this->length)" : 'int';
        if ($this->unsigned) {
            $result .= ' unsigned';
        }

        return $this->size == DBColumn::NORMAL ? $result : $this->size . $result;
    }

    public function prepareNull(): string
    {
        return $this->auto_increment || $this->default !== null
            ? 'NOT NULL'
            : 'NULL';
    }

    public function prepareDefault(): string
    {
        if ($this->auto_increment) {
            return ''; // no default for auto increment columns
        } else {
            return parent::prepareDefault();
        }
    }

    public function prepareModelDefinition(): string
    {
        if ($this->getName() == 'id') {
            $length = '';

            switch ($this->getSize()) {
                case DBColumn::TINY:
                    $length .= 'DBColumn::TINY';
                    break;
                case DBColumn::SMALL:
                    $length .= 'DBColumn::SMALL';
                    break;
                case DBColumn::MEDIUM:
                    $length .= 'DBColumn::MEDIUM';
                    break;
                case DBColumn::BIG:
                    $length .= 'DBColumn::BIG';
                    break;
            }

            return "DBIdColumn::create($length)";
        } else {
            $default = $this->getDefault() === null ? '' : ', ' . var_export($this->getDefault(), true);

            $result = "DBIntegerColumn::create('" . $this->getName() . "', " . $this->getLength() . "$default)";

            if ($this->unsigned) {
                $result .= '->setUnsigned(true)';
            }

            if ($this->auto_increment) {
                $result .= '->setAutoIncrement(true)';
            }

            return $result;
        }
    }

    public function getPhpType(): string
    {
        return 'int';
    }

    public function getCastingCode(): string
    {
        return '(int) $value';
    }

    public function getAutoIncrement(): bool
    {
        return $this->auto_increment;
    }

    public function &setAutoIncrement(bool $value): DBIntegerColumn
    {
        $this->auto_increment = (bool) $value;

        return $this;
    }
}
