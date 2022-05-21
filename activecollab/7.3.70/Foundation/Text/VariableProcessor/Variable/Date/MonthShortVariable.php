<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date;

use Angie\Globalization;

class MonthShortVariable extends MonthVariable
{
    public function getDescription(): string
    {
        return 'Current month, short';
    }

    public function process(int $num_modifier): string
    {
        return Globalization::getMonthName(
            $this->getModifiedReferneceMonth(
                $this->getReferenceDate()->getMonth(),
                $num_modifier
            ),
            true
        );
    }

    protected function getDateReferencedVariableName(): string
    {
        return sprintf('%sshort', parent::getDateReferencedVariableName());
    }
}
