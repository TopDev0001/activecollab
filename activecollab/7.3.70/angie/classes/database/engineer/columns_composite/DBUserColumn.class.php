<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class DBUserColumn extends DBCompositeColumn
{
    protected string $name;
    private bool $add_key;
    private string $id_column_name;
    private string $name_column_name;
    private string $email_column_name;

    public function __construct(string $name, bool $add_key = true)
    {
        $this->add_key = $add_key;
        $this->name = $name;

        $this->id_column_name = sprintf('%s_id', $name);
        $this->name_column_name = sprintf('%s_name', $name);
        $this->email_column_name = sprintf('%s_email', $name);

        $this->columns = [
            DBIntegerColumn::create($this->id_column_name, 10)->setUnsigned(true),
            DBStringColumn::create($this->name_column_name, 100),
            DBStringColumn::create($this->email_column_name, 150),
        ];
    }

    public function addedToTable(): void
    {
        if ($this->add_key) {
            $this->table->addIndex(
                new DBIndex(
                    $this->id_column_name,
                    DBIndex::KEY,
                    $this->id_column_name
                )
            );
        }

        parent::addedToTable();
    }
}
