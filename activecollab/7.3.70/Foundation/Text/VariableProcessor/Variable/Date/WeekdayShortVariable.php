<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date;

use Angie\Globalization;

class WeekdayShortVariable extends WeekdayVariable
{
    public function getDescription(): string
    {
        return 'Day of the week, short';
    }

    public function process(int $num_modifier): string
    {
        return Globalization::getDayName(
            $this->getModifiedWeekday($this->getReferenceDate()->getWeekday(), $num_modifier),
            true
        );
    }

    protected function getDateReferencedVariableName(): string
    {
        return sprintf('%sshort', parent::getDateReferencedVariableName());
    }
}
