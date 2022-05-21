<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date;

class WeekdayNumVariable extends WeekdayVariable
{
    public function getDescription(): string
    {
        return 'ISO day of the week';
    }

    public function process(int $num_modifier): string
    {
        $weekday = $this->getModifiedWeekday(
            $this->getReferenceDate()->getWeekday(),
            $num_modifier
        );

        if ($weekday === 0) {
            return '7';
        }

        return (string) $weekday;
    }

    protected function getDateReferencedVariableName(): string
    {
        return sprintf('%snum', parent::getDateReferencedVariableName());
    }
}
