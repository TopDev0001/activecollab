<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Storage\StorageOverusedNotifier;

use ActiveCollab\CurrentTimestamp\CurrentTimestampInterface;
use ActiveCollab\Foundation\App\RootUrl\RootUrlInterface;
use ActiveCollab\Logger\LoggerInterface;
use Angie\Memories\MemoriesWrapperInterface;
use Angie\Notifications\NotificationsInterface;
use Angie\Storage\Capacity\StorageCapacityCalculatorInterface;
use StorageOverusedNotification;

class StorageOverusedNotifier implements StorageOverusedNotifierInterface
{
    private $notifications;
    private $memories;
    private $storage_capacity_calculator;
    private $current_timestamp;
    private $logger;
    private $root_url;

    public function __construct(
        NotificationsInterface $notifications,
        MemoriesWrapperInterface $memories,
        StorageCapacityCalculatorInterface $storage_capacity_calculator,
        CurrentTimestampInterface $current_timestamp,
        RootUrlInterface $root_url,
        LoggerInterface $logger
    )
    {
        $this->notifications = $notifications;
        $this->memories = $memories;
        $this->current_timestamp = $current_timestamp;
        $this->storage_capacity_calculator = $storage_capacity_calculator;
        $this->root_url = $root_url;
        $this->logger = $logger;
    }

    public function notifyAdministrators()
    {
        $last_notification_sent = $this->memories->get(self::LAST_NOTIFICATION_FOR_STORAGE_OVERUSED_MEMORY_KEY);
        $current_timestamp = $this->current_timestamp->getCurrentTimestamp();

        // send notification if one hasn't been sent ever before, or if it has been sent more then 7 days ago
        if (!$last_notification_sent || strtotime('+7 days', $last_notification_sent) < $current_timestamp) {
            /** @var StorageOverusedNotification $notification */
            $notification = $this->notifications->notifyAbout('system/storage_overused');
            $notification->setDiskSpaceLimit(
                format_file_size($this->storage_capacity_calculator->getCapacity())
            );
            $notification->setStorageAddOnsUrl($this->root_url->expandRelativeUrl('/subscription/manage-add-ons/storage'));
            $notification->sendToAdministrators();

            // remember when last notification has been sent
            $this->memories->set(
                self::LAST_NOTIFICATION_FOR_STORAGE_OVERUSED_MEMORY_KEY,
                $current_timestamp
            );
        } else {
            $this->logger->notice('Storage overused notification already sent in the past 7 days');
        }
    }
}
