<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\ActiveCollabCliCommandExecutor;

interface ActiveCollabCliCommandExecutorInterface
{
    const INSTANCE_TYPE_FEATHER = 'feather';

    public function execute(
        string $command_name,
        array $command_arguments,
        string $channel,
        bool $async = true
    ): void;
}
