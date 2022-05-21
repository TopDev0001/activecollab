<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date;

class QuarterNumVariable extends QuarterVariable
{
    public function getDescription(): string
    {
        return 'Quarter number';
    }

    public function process(int $num_modifier): string
    {
        return (string) $this->getModifiedQuarter(
            $this->getReferenceDate()->getQuarter(),
            $num_modifier
        );
    }

    protected function getDateReferencedVariableName(): string
    {
        return sprintf('%snum', parent::getDateReferencedVariableName());
    }
}
