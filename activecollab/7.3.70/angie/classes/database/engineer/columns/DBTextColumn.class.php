<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class DBTextColumn extends DBColumn
{
    protected bool $has_size = true;
    protected bool $has_default = false;

    public function __construct(string $name)
    {
        parent::__construct($name);
    }

    public function prepareTypeDefinition(): string
    {
        switch ($this->size) {
            case self::BIG:
                return 'longtext';
            case self::SMALL:
            case self::NORMAL:
                return 'text';
            default:
                return $this->size . 'text';
        }
    }

    public function prepareModelDefinition(): string
    {
        if ($this->name == 'raw_additional_properties') {
            return 'DBAdditionalPropertiesColumn::create()';
        } else {
            $result = "DBTextColumn::create('" . $this->getName() . "')";

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
    }
}
