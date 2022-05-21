<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\NewFeatures;

use ActiveCollab\CurrentTimestamp\CurrentTimestampInterface;
use ActiveCollab\Module\System\Utils\NewFeatures\CallToAction\PersonalizerCallToActionInterface;
use ActiveCollab\Module\System\Utils\NewFeatures\NewFeatureAnnouncementsLoader\NewFeatureAnnouncementsLoaderInterface;
use ConfigOptions;
use DateTimeValue;
use DateValue;
use DB;
use User;

class NewFeaturesManager implements NewFeaturesManagerInterface
{
    /**
     * @var NewFeatureAnnouncementInterface[]
     */
    private $new_feature_announcements = [];
    private $channel;
    private $current_timestamp;

    public function __construct(
        NewFeatureAnnouncementsLoaderInterface $new_feature_announcements_loader,
        string $channel,
        CurrentTimestampInterface $current_timestamp
    )
    {
        $this->new_feature_announcements = $new_feature_announcements_loader->getNewFeatureAnnouncements();
        $this->channel = $channel;
        $this->current_timestamp = $current_timestamp;
    }

    /**
     * Get the list of new features for the user.
     *
     * @return NewFeatureAnnouncementInterface[]
     */
    public function get(User $user, DateValue $date = null, bool $record_last_visit = false): array
    {
        if (empty($date)) {
            $date = new DateValue();
        }

        $result = [];

        foreach ($this->new_feature_announcements as $new_feature_announcement) {
            if (!$new_feature_announcement->isVisibleToUser($user)) {
                continue;
            }

            if (!$new_feature_announcement->isVisibleInChannel($this->channel)) {
                continue;
            }

            if (!$new_feature_announcement->isVisibleOnDate($date)) {
                continue;
            }

            $result[] = $new_feature_announcement;
        }

        if ($record_last_visit) {
            $this->recordLastVisit($user);
        }

        return $result;
    }

    public function getJson(User $user, DateValue $date = null, bool $record_last_visit = false): array
    {
        $result = [];

        $last_visit_timestamp = $this->getLastVisit($user);

        foreach ($this->get($user, $date, $record_last_visit) as $new_feature_announcement) {
            $announcement_data = array_merge(
                $new_feature_announcement->jsonSerialize(),
                [
                    'is_seen' => $new_feature_announcement->isSeen($last_visit_timestamp),
                ]
            );

            $call_to_action = $new_feature_announcement->getCallToAction();

            if ($call_to_action instanceof PersonalizerCallToActionInterface) {
                $announcement_data['action_url'] = $call_to_action->getPersonalizedUrl($user);
            }

            $result[] = $announcement_data;
        }

        return $result;
    }

    /**
     * Count new features for user.
     */
    public function countUnseen(User $user, DateValue $date = null): int
    {
        if ($this->areMuted($user)) {
            return 0;
        }

        if (empty($date)) {
            $date = new DateValue();
        }

        $new_feature_announcements = $this->get($user, $date, false);
        $unseen_count = count($new_feature_announcements);

        $last_visit_timestamp = $this->getLastVisit($user);

        if (empty($last_visit_timestamp)) {
            return $unseen_count;
        }

        foreach ($new_feature_announcements as $new_feature_announcement) {
            if ($new_feature_announcement->isSeen($last_visit_timestamp)) {
                $unseen_count--;
            }
        }

        return $unseen_count;
    }

    private function areMuted(User $user): bool
    {
        return !ConfigOptions::getValueFor('new_features_notification', $user);
    }

    public function getLastVisit(User $user): ?DateTimeValue
    {
        $new_features_timestamp = ConfigOptions::getValueFor('new_features_timestamp', $user);
        if ($new_features_timestamp && is_int($new_features_timestamp)) {
            return new DateTimeValue($new_features_timestamp);
        }

        return $user->getCreatedOn();
    }

    public function recordLastVisit(User $user, DateTimeValue $timestamp = null): DateTimeValue
    {
        if (empty($timestamp)) {
            $timestamp = new DateTimeValue();
        }

        DB::transact(
            function () use ($user, $timestamp) {
                ConfigOptions::setValueFor(
                    'new_features_timestamp',
                    $user,
                    $timestamp->getTimestamp()
                );

                $user->touch();
            }
        );

        return $timestamp;
    }
}
