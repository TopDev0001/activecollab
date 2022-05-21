<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date;

class YearShortVariable extends DateReferencedVariable
{
    public function getDescription(): string
    {
        return 'Current year, short';
    }

    public function process(int $num_modifier): string
    {
        return substr(
            (string) ($this->getReferenceDate()->getYear() + $num_modifier),
            2
        );
    }

    protected function getDateReferencedVariableName(): string
    {
        return 'yearshort';
    }
}
