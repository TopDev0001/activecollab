<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date;

use DateValue;

class WeekVariable extends DateReferencedVariable
{
    public function getDescription(): string
    {
        return 'ISO week number';
    }

    public function process(int $num_modifier): string
    {
        return (string) $this->getModifiedReferneceDate($num_modifier)->getWeek();
    }

    protected function getDateReferencedVariableName(): string
    {
        return 'week';
    }

    protected function getModifiedReferneceDate(int $num_modifier): DateValue
    {
        return $this->getReferenceDate()->advance($num_modifier * 604800, false);
    }
}
