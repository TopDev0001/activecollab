<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\ExpiredFeaturePointersCleaner;

use ActiveCollab\Logger\LoggerInterface;
use ActiveCollab\Module\System\Events\Maintenance\DailyMaintenanceEvent;
use DateValue;
use DB;
use Exception;
use FeaturePointers;

class ExpiredFeaturePointersCleaner implements ExpiredFeaturePointersCleanerInterface
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    public function cleanExpiredFeaturePointers(): void
    {
        try {
            $feature_ids = DB::executeFirstColumn('SELECT id FROM feature_pointers WHERE expires_on < ?', new DateValue());

            if (!empty($feature_ids)) {
                DB::execute('DELETE from feature_pointer_dismissals WHERE feature_pointer_id IN (?)', $feature_ids);
                FeaturePointers::delete(['id IN (?)', $feature_ids]);
            }
        } catch (Exception $exception) {
            $this->logger->error('Failed to clean Expired Feature Pointers:', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function __invoke(DailyMaintenanceEvent $event)
    {
        $this->cleanExpiredFeaturePointers();
    }
}
