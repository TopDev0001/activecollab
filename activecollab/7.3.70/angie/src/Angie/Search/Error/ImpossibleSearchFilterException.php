<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\Error;

use Angie\Error;

final class ImpossibleSearchFilterException extends Error
{
    public function __construct(
        $message = 'Impossible search filter.',
        $additional = null,
        $previous = null
    )
    {
        parent::__construct($message, $additional, $previous);
    }
}
