<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\NewFeatures\CallToAction;

interface CallToActionInterface
{
    public function getTitle(): string;
    public function getUrl(): string;
}
