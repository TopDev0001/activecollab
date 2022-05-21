<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class DBBinaryColumn extends DBColumn
{
    protected bool $has_size = true;
    protected bool $has_default = false;

    public function prepareModelDefinition(): string
    {
        $result = "DBBinaryColumn::create('" . $this->getName() . "')";

        if ($this->getSize() != DBColumn::NORMAL) {
            switch ($this->getSize()) {
                case DBColumn::TINY:
                    $result .= '->setSize(DBColumn::TINY)';
                    break;
                case DBColumn::SMALL:
                    $result .= '->setSize(DBColumn::SMALL)';
                    break;
                case DBColumn::MEDIUM:
                    $result .= '->setSize(DBColumn::MEDIUM)';
                    break;
                case DBColumn::BIG:
                    $result .= '->setSize(DBColumn::BIG)';
                    break;
            }
        }

        return $result;
    }

    public function prepareTypeDefinition(): string
    {
        switch ($this->size) {
            case self::BIG:
                return 'longblob';
            case self::SMALL:
            case self::NORMAL:
                return 'blob';
            default:
                return $this->size . 'blob';
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
