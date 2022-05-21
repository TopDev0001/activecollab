<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Features;

use Angie\Features\FeatureInterface;

interface HideFromClientsFeatureInterface extends FeatureInterface
{
    const NAME = 'hide_from_clients';
    const VERBOSE_NAME = 'Hide From Clients';
}
