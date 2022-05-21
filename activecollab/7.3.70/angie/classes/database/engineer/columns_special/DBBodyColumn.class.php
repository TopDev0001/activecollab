<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class DBBodyColumn extends DBCompositeColumn
{
    private bool $add_body_trait;

    public function __construct(bool $add_body_trait = true, bool $add_body_mode = true)
    {
        $this->columns = [
            (new DBTextColumn('body'))->setSize(DBTextColumn::BIG),
        ];

        if ($add_body_mode) {
            $this->columns[] = new DBEnumColumn(
                'body_mode',
                [
                    'paragraph',
                    'break-line',
                ],
                'paragraph'
            );
        }

        $this->add_body_trait = $add_body_trait;
    }

    public function addedToTable(): void
    {
        if ($this->add_body_trait) {
            $this->table->addModelTrait(IBody::class, IBodyImplementation::class);
        }

        parent::addedToTable();
    }
}
