<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Mail;

interface MailRouterInterface
{
    const MAIL_ROUTE_DEFAULT = 'default';

    public function getMailRoute(): ?string;
}
