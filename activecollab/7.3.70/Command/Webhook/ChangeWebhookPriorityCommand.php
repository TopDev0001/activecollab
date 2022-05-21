<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Command\Webhook;

use ActiveCollab\Foundation\Webhooks\WebhookInterface;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Webhook;

class ChangeWebhookPriorityCommand extends WithSelectedWebhookCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Change priority of the selected webhook.')
            ->addArgument('new_priority', InputArgument::REQUIRED, 'New priority value');
    }

    protected function withSelectedWebhook(Webhook $webhook, InputInterface $input): string
    {
        $new_priority = $this->mustGetNewPrirority($input);
        $old_value = $webhook->getPriority();

        if ($new_priority === $old_value) {
            return sprintf(
                'Webhook <comment>#%d</comment> already has priority set to <comment>%s</comment>.',
                $webhook->getId(),
                $webhook->getPriority(),
            );
        }

        $webhook->setPriority($new_priority);
        $webhook->save();

        return sprintf(
            'Priority changed from <comment>%s</comment> to <comment>%s</comment> for webhook <comment>#%d</comment>.',
            $old_value,
            $webhook->getPriority(),
            $webhook->getId(),
        );
    }

    private function mustGetNewPrirority(InputInterface $input): string
    {
        $priority = $input->getArgument('new_priority');

        if (!in_array($priority, WebhookInterface::PRIORITIES)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Value "%s" is not a valid webhook prioroty.',
                    $priority
                )
            );
        }

        return $priority;
    }
}
