<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\FeatureEvents;

use ActiveCollab\EventsDispatcher\Events\Event;
use Angie\Features\FeatureInterface;

abstract class FeatureEvent extends Event implements FeatureEventInterface
{
    private $feature;

    public function __construct(FeatureInterface $feature)
    {
        $this->feature = $feature;
    }

    public function getFeature(): FeatureInterface
    {
        return $this->feature;
    }
}
