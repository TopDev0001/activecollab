<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Command\Webhook;

use AngieApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use User;
use Webhook;
use WebhookDisabledNotification;

class DisableWebhookCommand extends WithSelectedWebhookCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Disable a webhook by their ID or URL.')
            ->addOption('delete', '', InputOption::VALUE_NONE, 'Delete the webhook.')
            ->addOption(
                'notify-creator',
                '',
                InputOption::VALUE_NONE,
                'Notify person who created the webhook.'
            );
    }

    protected function withSelectedWebhook(Webhook $webhook, InputInterface $input): string
    {
        if ($this->shouldDelete($input)) {
            $webhook->delete();

            return sprintf(
                'Webhook <comment>#%d</comment> (<comment>%s</comment>) is deleted!',
                $webhook->getId(),
                $webhook->getUrl()
            );
        }

        $webhook->setIsEnabled(false);
        $webhook->save();

        if ($input->getOption('notify-creator')) {
            $this->notifyCreator($webhook);
        }

        return sprintf(
            'Webhook <comment>#%d</comment> (<comment>%s</comment>) is disabled.',
            $webhook->getId(),
            $webhook->getUrl()
        );
    }

    private function shouldDelete(InputInterface $input): bool
    {
        return (bool) $input->getOption('delete');
    }

    private function notifyCreator(Webhook $webhook): void
    {
        $webhook_created_by = $webhook->getCreatedBy();

        /** @var WebhookDisabledNotification $notification */
        $notification = AngieApplication::notifications()->notifyAbout('system/webhook_disabled');
        $notification
            ->setWebhook($webhook)
            ->setWebhooksUrl(sprintf('%s/integrations/webhooks', ROOT_URL));

        if ($webhook_created_by instanceof User && $webhook_created_by->isActive()) {
            $notification->sendToUsers(
                [
                    $webhook_created_by,
                ]
            );

            return;
        }

        $notification->sendToAdministrators();
    }
}
