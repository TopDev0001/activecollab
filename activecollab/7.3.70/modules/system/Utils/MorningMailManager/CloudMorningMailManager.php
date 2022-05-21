<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\MorningMailManager;

use AccountStatusInterface;
use DateValue;
use MorningPaper;

class CloudMorningMailManager implements MorningMailManagerInterface
{
    private $account_status;

    public function __construct(AccountStatusInterface $account_status)
    {
        $this->account_status = $account_status;
    }

    public function send(DateValue $day, array $users = null): void
    {
        if ($this->shouldSend()) {
            MorningPaper::send($day, $users);
        }
    }

    private function shouldSend(): bool
    {
        return !$this->account_status->isSuspended() && !$this->account_status->isRetired();
    }
}
