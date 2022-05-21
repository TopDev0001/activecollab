<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date;

class MonthNumVariable extends MonthVariable
{
    public function getDescription(): string
    {
        return 'Current month, number';
    }

    public function process(int $num_modifier): string
    {
        return (string) $this->getModifiedReferneceMonth(
            $this->getReferenceDate()->getMonth(),
            $num_modifier
        );
    }

    protected function getDateReferencedVariableName(): string
    {
        return sprintf('%snum', parent::getDateReferencedVariableName());
    }
}
