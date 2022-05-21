<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Command\Webhook;

use Symfony\Component\Console\Input\InputInterface;
use Webhook;

class EnableWebhookCommand extends WithSelectedWebhookCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Enable a webhook by their ID or URL.');
    }

    protected function withSelectedWebhook(Webhook $webhook, InputInterface $input): string
    {
        $webhook->setIsEnabled(true);
        $webhook->save();

        return sprintf(
            'Webhook <comment>#%d</comment> (<comment>%s</comment>) is enabled.',
            $webhook->getId(),
            $webhook->getUrl()
        );
    }
}
