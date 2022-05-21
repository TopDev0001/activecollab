<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Command\Webhook;

use DataObjectPool;
use Exception;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webhook;
use Webhooks;

class ListCommand extends WebhookCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setDescription('List all webhooks.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            /** @var Webhook[] $webhooks */
            $webhooks = Webhooks::find(
                [
                    'order_by' => 'id',
                ]
            );

            if (!empty($webhooks)) {
                $table = new Table($output);
                $table->setHeaders(
                    [
                        '#',
                        'Integration',
                        'Enabled',
                        'Priority',
                        'Url',
                    ]
                );

                foreach ($webhooks as $webhook) {
                    $integration = $webhook->getIntegrationId()
                        ? DataObjectPool::get(\Integration::class, $webhook->getIntegrationId())
                        : null;

                    $table->addRow(
                        [
                            $webhook->getId(),
                            $integration ? $integration->getName() : null,
                            $webhook->getIsEnabled() ? '<info>Yes</info>' : '<comment>No</comment>',
                            $webhook->getPriority(),
                            $webhook->getUrl(),
                        ]
                    );
                }

                $table->render();

                if (count($webhooks) === 1) {
                    $output->writeln('<comment>One</comment> webhook found.');
                } else {
                    $output->writeln(sprintf('<comment>%d</comment> webhooks found.', count($webhooks)));
                }
            } else {
                $output->writeln(sprintf('<comment>%d</comment> webhooks found.', 0));
            }

            return 0;
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }
}
