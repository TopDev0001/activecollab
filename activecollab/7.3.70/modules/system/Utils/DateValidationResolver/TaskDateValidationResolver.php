<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\DateValidationResolver;

use DateValue;

class TaskDateValidationResolver implements DateValidationResolverInterface
{
    /**
     * @var DateValue
     */
    private $min;

    /**
     * @var DateValue
     */
    private $max;

    public function __construct(DateValue $min, DateValue $max)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public function isValid(DateValue $date): bool
    {
        return $this->min->getTimestamp() <= $date->getTimestamp()
            && $date->getTimestamp() <= $this->max->getTimestamp();
    }
}
