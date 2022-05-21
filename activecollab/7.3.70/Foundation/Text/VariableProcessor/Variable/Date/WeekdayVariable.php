<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date;

use Angie\Globalization;

class WeekdayVariable extends DateReferencedVariable
{
    public function getDescription(): string
    {
        return 'Day of the week';
    }

    public function process(int $num_modifier): string
    {
        return Globalization::getDayName(
            $this->getModifiedWeekday(
                $this->getReferenceDate()->getWeekday(),
                $num_modifier
            )
        );
    }

    protected function getDateReferencedVariableName(): string
    {
        return 'weekday';
    }

    protected function getModifiedWeekday(int $current_weekday, $num_modifier): int
    {
        $result = ($current_weekday + $num_modifier) % 7;

        if ($result >= 0) {
            return $result;
        }

        return 7 - abs($result);
    }
}
