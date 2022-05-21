<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\EventListeners\FeatureEvents;

use ActiveCollab\EventsDispatcher\Events\EventInterface;
use ActiveCollab\Logger\LoggerInterface;
use ActiveCollab\Module\System\Events\FeatureEvents\FeatureEventInterface;
use ActiveCollab\Module\System\Features\InvoicesFeature;
use ConfigOptions;
use RecurringProfile;
use RecurringProfiles;
use Throwable;

class FeatureDeactivated implements EventInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(FeatureEventInterface $event)
    {
        if ($event->getFeature() instanceof InvoicesFeature) {
            try {
                /** @var RecurringProfile[]|null $recurring_profiles */
                $recurring_profiles = RecurringProfiles::findBy('is_enabled', true);

                if (is_foreachable($recurring_profiles)) {
                    foreach ($recurring_profiles as $recurring_profile) {
                        $recurring_profile->setIsEnabled(false);
                        $recurring_profile->save();
                    }
                }

                ConfigOptions::setValue('invoice_overdue_reminders_enabled', false, true);
            } catch (Throwable $e) {
                $this->logger->error('Failed to disable recurring profiles upon invoices feature deactivation', [
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ]);

                throw $e;
            }
        }
    }
}
