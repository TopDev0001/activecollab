<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date;

use Angie\Globalization;

class MonthVariable extends DateReferencedVariable
{
    public function getDescription(): string
    {
        return 'Current month';
    }

    public function process(int $num_modifier): string
    {
        return Globalization::getMonthName(
            $this->getModifiedReferneceMonth(
                $this->getReferenceDate()->getMonth(),
                $num_modifier
            )
        );
    }

    protected function getDateReferencedVariableName(): string
    {
        return 'month';
    }

    protected function getModifiedReferneceMonth(int $current_month, int $num_modifier): int
    {
        $result = ($current_month + $num_modifier) % 12;

        if ($result === 0) {
            return 12;
        }

        if ($result > 0) {
            return $result;
        }

        return 12 - abs($result);
    }
}
