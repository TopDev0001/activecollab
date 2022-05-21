<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class DBDateTimeColumn extends DBColumn
{
    public function __construct(string $name)
    {
        parent::__construct($name);
    }

    public function prepareTypeDefinition(): string
    {
        return 'datetime';
    }

    public function prepareDefault(): string
    {
        if ($this->default === null) {
            return 'NULL';
        } else {
            return is_int($this->default)
                ? "'" . date(DATETIME_MYSQL, $this->default) . "'"
                : "'" . date(DATETIME_MYSQL, strtotime($this->default)) . "'";
        }
    }

    public function getPhpType(): string
    {
        return DateTimeValue::class;
    }

    public function getCastingCode(): string
    {
        return 'datetimeval($value)';
    }
}
