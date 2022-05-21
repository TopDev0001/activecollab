<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\MorningMailManager;

use DateValue;
use MorningPaper;

class SelfHostedMorningMailManager implements MorningMailManagerInterface
{
    public function send(DateValue $day, array $users = null): void
    {
        MorningPaper::send($day, $users);
    }
}
