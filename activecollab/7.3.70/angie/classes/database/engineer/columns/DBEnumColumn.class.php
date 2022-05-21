<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class DBEnumColumn extends DBColumn
{
    private array $possibilities = [];

    public function __construct(
        string $name,
        array $possibilities = null,
        $default = null
    )
    {
        parent::__construct($name, $default);

        if (!empty($possibilities)) {
            $this->possibilities = $possibilities;
        }
    }

    public function processAdditional(array $additional): void
    {
        parent::processAdditional($additional);

        if (!empty($additional[0])) {
            $this->possibilities = $additional;
        }
    }

    public function prepareTypeDefinition(): string
    {
        $possibilities = [];
        foreach ($this->possibilities as $v) {
            $possibilities[] = var_export((string) $v, true);
        }

        return 'enum(' . implode(', ', $possibilities) . ')';
    }

    public function prepareModelDefinition(): string
    {
        $possibilities = [];

        foreach ($this->getPossibilities() as $v) {
            $possibilities[] = var_export($v, true);
        }

        $possibilities = 'array(' . implode(', ', $possibilities) . ')';

        $default = $this->getDefault() === null ? '' : ', ' . var_export($this->getDefault(), true);

        return "DBEnumColumn::create('" . $this->getName() . "', $possibilities$default)";
    }

    public function getPossibilities(): array
    {
        return $this->possibilities;
    }

    public function setPossibilities(array $value): DBEnumColumn
    {
        $this->possibilities = $value;

        return $this;
    }

    public function getCastingCode(): string
    {
        return '(empty($value) ? null : (string) $value)';
    }
}
