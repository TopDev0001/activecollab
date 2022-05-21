<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class DBDateColumn extends DBColumn
{
    public function __construct(string $name)
    {
        parent::__construct($name);
    }

    public function prepareTypeDefinition(): string
    {
        return 'date';
    }

    public function prepareDefault(): string
    {
        if ($this->default === null) {
            return 'NULL';
        } else {
            return is_int($this->default)
                ? "'" . date(DATE_MYSQL, $this->default) . "'"
                : "'" . date(DATE_MYSQL, strtotime($this->default)) . "'";
        }
    }

    public function getPhpType(): string
    {
        return DateValue::class;
    }

    public function getCastingCode(): string
    {
        return 'dateval($value)';
    }
}
