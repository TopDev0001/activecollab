<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date;

class DateIsoVariable extends DateVariable
{
    public function getDescription(): string
    {
        return 'Current date, ISO format';
    }

    public function process(int $num_modifier): string
    {
        return $this->getModifiedReferneceDate($num_modifier)->format('Y-m-d');
    }

    protected function getDateReferencedVariableName(): string
    {
        return sprintf('%siso', parent::getDateReferencedVariableName());
    }
}
