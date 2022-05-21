<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

class ImpossibleCollectionError extends Error
{
    public function __construct($message = null)
    {
        if (empty($message)) {
            $message = 'Collection cannot be prepared based on given parameters';
        }

        parent::__construct($message);
    }
}
