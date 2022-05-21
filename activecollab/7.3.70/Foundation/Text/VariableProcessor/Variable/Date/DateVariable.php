<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date;

use DateValue;

class DateVariable extends DateReferencedVariable
{
    public function getDescription(): string
    {
        return 'Current date';
    }

    public function process(int $num_modifier): string
    {
        return $this->getModifiedReferneceDate($num_modifier)->formatDateForUser();
    }

    protected function getDateReferencedVariableName(): string
    {
        return 'date';
    }

    protected function getModifiedReferneceDate(int $num_modifier): DateValue
    {
        return $this->getReferenceDate()->advance($num_modifier * 86400, false);
    }
}
