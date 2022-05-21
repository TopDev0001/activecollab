<?php

/*
 * This file is part of the Active Collab DateValue project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DateValue;

use IteratorAggregate;
use JsonSerializable;

interface DateRangeInterface extends IteratorAggregate, JsonSerializable
{
    public function getStartDate(): DateValueInterface;
    public function getEndDate(): DateValueInterface;
    public function getStartDateTime(): DateTimeValueInterface;
    public function getEndDateTime(): DateTimeValueInterface;
}
