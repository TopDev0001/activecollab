<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\NewFeatures;

use ActiveCollab\Module\System\Utils\NewFeatures\CallToAction\CallToActionInterface;
use DateTimeValue;
use DateValue;
use User;

class NewFeatureAnnouncement implements NewFeatureAnnouncementInterface
{
    private $title;
    private $description;
    private $date;
    private $call_to_action;
    private $user_visibility_callback;
    private $channel_visibility;

    public function __construct(
        string $title,
        string $description,
        DateValue $date,
        CallToActionInterface $call_to_action = null,
        callable $user_visibility_callback = null,
        array $channel_visibility = null
    )
    {
        $this->title = $title;
        $this->description = $description;
        $this->date = $date;
        $this->call_to_action = $call_to_action;
        $this->user_visibility_callback = $user_visibility_callback;
        $this->channel_visibility = $channel_visibility;
    }

    public function getCallToAction(): ?CallToActionInterface
    {
        return $this->call_to_action;
    }

    public function isVisibleToUser(User $user): bool
    {
        if (empty($this->user_visibility_callback)) {
            return true;
        }

        return call_user_func($this->user_visibility_callback, $user);
    }

    public function isVisibleInChannel(string $channel): bool
    {
        if (empty($this->channel_visibility)) {
            return true;
        }

        return in_array($channel, $this->channel_visibility);
    }

    public function isVisibleOnDate(DateValue $date): bool
    {
        return $date->getTimestamp() >= $this->date->getTimestamp();
    }

    public function isSeen(?DateTimeValue $timestamp): bool
    {
        return $timestamp && $timestamp->getTimestamp() >= $this->date->getTimestamp();
    }

    public function jsonSerialize(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'date' => $this->date->format('Y-m-d'),
            'action_title' => $this->call_to_action ? $this->call_to_action->getTitle() : null,
            'action_url' => $this->call_to_action ? $this->call_to_action->getUrl() : null,
        ];
    }
}
