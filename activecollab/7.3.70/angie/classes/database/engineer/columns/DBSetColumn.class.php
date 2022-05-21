<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class DBSetColumn extends DBColumn
{
    private array $possibilities;

    public function __construct(string $name, array $possibilities = [], $default = null)
    {
        parent::__construct($name, $default);

        $this->possibilities = $possibilities;
    }

    public function processAdditional(array $additional): void
    {
        parent::processAdditional($additional);

        if (!empty($additional[0])) {
            $this->possibilities = $additional;
        }
    }

    public function prepareDefault(): string
    {
        if (is_array($this->default)) {
            return "'" . implode(',', $this->default) . "'";
        } elseif ($this->default === null) {
            return 'NULL';
        } else {
            return '';
        }
    }

    public function prepareTypeDefinition(): string
    {
        $possibilities = [];
        foreach ($this->possibilities as $v) {
            $possibilities[] = var_export((string) $v, true);
        }

        return 'set(' . implode(', ', $possibilities) . ')';
    }

    public function prepareModelDefinition(): string
    {
        $possibilities = [];

        foreach ($this->getPossibilities() as $v) {
            $possibilities[] = var_export($v, true);
        }

        $possibilities = 'array(' . implode(', ', $possibilities) . ')';

        $default = $this->getDefault() === null ? '' : ', ' . var_export($this->getDefault(), true);

        return "DBSetColumn::create('" . $this->getName() . "', $possibilities$default)";
    }

    public function getPossibilities(): array
    {
        return $this->possibilities;
    }

    public function loadFromRow(array $row): void
    {
        parent::loadFromRow($row);

        if (isset($row['Default']) && $row['Default']) {
            $this->setDefault(explode(',', $row['Default']));
        }
    }

    public function getPhpType(): string
    {
        return 'mixed';
    }

    public function getCastingCode(): string
    {
        return '$value';
    }
}
