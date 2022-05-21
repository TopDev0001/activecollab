<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\VariableProcessor\VariableProvider;

use ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date\DateIsoVariable;
use ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date\DateVariable;
use ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date\DayVariable;
use ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date\MonthDayVariable;
use ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date\MonthNumVariable;
use ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date\MonthShortVariable;
use ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date\MonthVariable;
use ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date\QuarterNumVariable;
use ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date\QuarterVariable;
use ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date\WeekdayNumVariable;
use ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date\WeekdayShortVariable;
use ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date\WeekdayVariable;
use ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date\WeekVariable;
use ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date\YearShortVariable;
use ActiveCollab\Foundation\Text\VariableProcessor\Variable\Date\YearVariable;
use DateValue;

class DateVariableProvider extends VariableProvider
{
    private DateValue $reference;
    private string $prefix;

    public function __construct(DateValue $reference, string $prefix = '')
    {
        $this->reference = $reference;
        $this->prefix = $prefix;
    }

    public function getVariables(): array
    {
        return [
            new DateVariable($this->reference, $this->prefix),
            new DateIsoVariable($this->reference, $this->prefix),
            new WeekdayVariable($this->reference, $this->prefix),
            new WeekdayShortVariable($this->reference, $this->prefix),
            new WeekdayNumVariable($this->reference, $this->prefix),
            new DayVariable($this->reference, $this->prefix),
            new MonthDayVariable($this->reference, $this->prefix),
            new WeekVariable($this->reference, $this->prefix),
            new MonthVariable($this->reference, $this->prefix),
            new MonthShortVariable($this->reference, $this->prefix),
            new MonthNumVariable($this->reference, $this->prefix),
            new QuarterVariable($this->reference, $this->prefix),
            new QuarterNumVariable($this->reference, $this->prefix),
            new YearVariable($this->reference, $this->prefix),
            new YearShortVariable($this->reference, $this->prefix),
        ];
    }

    public function getVariableNames(): array
    {
        $result = [];

        foreach ($this->getVariables() as $variable) {
            $result[] = $variable->getName();
        }

        return $result;
    }
}
