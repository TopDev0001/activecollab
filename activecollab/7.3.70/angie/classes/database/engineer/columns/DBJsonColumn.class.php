<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class DBJsonColumn extends DBColumn
{
    public function prepareTypeDefinition(): string
    {
        return 'json';
    }

    public function getPhpType(): string
    {
        return 'mixed';
    }

    public function getCastingCode(): string
    {
        return 'json_encode($value)';
    }
}
