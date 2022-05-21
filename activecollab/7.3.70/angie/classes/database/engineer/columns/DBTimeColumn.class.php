<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class DBTimeColumn extends DBColumn
{
    public function prepareTypeDefinition(): string
    {
        return 'time';
    }

    public function getPhpType(): string
    {
        return DateTimeValue::class;
    }

    public function getCastingCode(): string
    {
        return 'timeval($value)';
    }
}
