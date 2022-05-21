<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class DBBoolColumn extends DBColumn
{
    public function __construct(string $name, bool $default = false)
    {
        parent::__construct($name, $default);
    }

    public function prepareTypeDefinition(): string
    {
        return 'tinyint(1) unsigned';
    }

    public function prepareNull(): string
    {
        return 'NOT NULL';
    }

    public function prepareDefault(): string
    {
        return $this->default ? "'1'" : "'0'";
    }

    public function prepareModelDefinition(): string
    {
        if ($this->getDefault() === null) {
            $default = '';
        } else {
            $default = $this->getDefault() ? ', true' : ', false';
        }

        return "DBBoolColumn::create('" . $this->getName() . "'$default)";
    }

    public function getPhpType(): string
    {
        return 'bool';
    }

    public function getCastingCode(): string
    {
        return '(bool) $value';
    }
}
