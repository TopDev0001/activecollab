<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Expander\Services\Warehouse;

class WarehouseFileDetails implements WarehouseFileDetailsInterface
{
    private string $file_name;
    private int $file_size;

    public function __construct(string $file_name, int $file_size)
    {
        $this->file_name = $file_name;
        $this->file_size = $file_size;
    }

    public function getFileName(): string
    {
        return $this->file_name;
    }

    public function getFileSize(): int
    {
        return $this->file_size;
    }
}
