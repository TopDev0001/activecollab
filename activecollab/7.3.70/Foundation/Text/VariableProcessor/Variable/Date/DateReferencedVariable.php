<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date;

use ActiveCollab\Foundation\Text\VariableProcessor\Variable\Variable;
use DateValue;

abstract class DateReferencedVariable extends Variable
{
    private DateValue $reference_date;

    public function __construct(DateValue $reference_date, string $prefix = '')
    {
        parent::__construct(
            sprintf(
                '%s%s',
                $prefix,
                $this->getDateReferencedVariableName()
            )
        );

        $this->reference_date = $reference_date;
    }

    public function supportsNumModifier(): bool
    {
        return true;
    }

    protected function getReferenceDate(): DateValue
    {
        return $this->reference_date;
    }

    abstract protected function getDateReferencedVariableName(): string;
}
