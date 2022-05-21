<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date;

class YearVariable extends DateReferencedVariable
{
    public function getDescription(): string
    {
        return 'Current year';
    }

    public function process(int $num_modifier): string
    {
        return (string) ($this->getReferenceDate()->getYear() + $num_modifier);
    }

    protected function getDateReferencedVariableName(): string
    {
        return 'year';
    }
}
