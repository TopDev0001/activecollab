<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date;

class QuarterVariable extends DateReferencedVariable
{
    public function getDescription(): string
    {
        return 'Quarter number, with prefix';
    }

    public function process(int $num_modifier): string
    {
        return sprintf(
            'Q%d',
            $this->getModifiedQuarter(
                $this->getReferenceDate()->getQuarter(),
                $num_modifier
            )
        );
    }

    protected function getDateReferencedVariableName(): string
    {
        return 'quarter';
    }

    protected function getModifiedQuarter(int $current_quarter, $num_modifier): int
    {
        $result = ($current_quarter + $num_modifier) % 4;

        if ($result === 0) {
            return 4;
        }

        if ($result > 0) {
            return $result;
        }

        return 4 - abs($result);
    }
}
