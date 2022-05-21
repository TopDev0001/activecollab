<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\TestCase;

use Angie\Events;
use AngieApplication;
use AngieApplicationModel;
use DataObjectPool;
use DateTimeValue;
use Owner;

abstract class ModelTestCase extends BaseTestCase
{
    protected Owner $owner;

    protected function setUp(): void
    {
        parent::setUp();

        AngieApplicationModel::revert('test');

        AngieApplication::cache()->clear();
        empty_dir(CACHE_PATH, true);

        DataObjectPool::clear();

        Events::trigger('on_reset_manager_states');

        $this->owner = DataObjectPool::get(Owner::class, 1);
        $this->assertTrue($this->owner->isLoaded());
    }

    protected function tearDown(): void
    {
        AngieApplication::onDemandStatus()->resetToDefault();

        if (DateTimeValue::isCurrentTimestampLocked()) {
            DateTimeValue::unlockCurrentTimestamp();
        }

        parent::tearDown();
    }
}
