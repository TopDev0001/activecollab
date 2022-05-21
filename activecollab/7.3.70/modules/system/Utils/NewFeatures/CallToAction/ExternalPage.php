<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\NewFeatures\CallToAction;

use InvalidArgumentException;

class ExternalPage extends CallToAction
{
    public function __construct(string $title, string $url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Valid URL expected.');
        }

        parent::__construct($title, $url);
    }
}
