<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date;

class MonthDayVariable extends DateReferencedVariable
{
    public function getDescription(): string
    {
        return 'Current day of the month';
    }

    public function process(int $num_modifier): string
    {
        return (string) $this->getReferenceDate()->getDay();
    }

    protected function getDateReferencedVariableName(): string
    {
        return 'monthday';
    }
}
