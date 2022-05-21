<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\MorningMailResolver;

use ActiveCollab\Module\System\Utils\MorningMailManager\MorningMailManagerInterface;

interface MorningMailResolverInterface
{
    public function getMorningMailManager(): MorningMailManagerInterface;
}
