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
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webhook;
use Webhooks;

abstract class WithSelectedWebhookCommand extends WebhookCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->addArgument('webhook_id_or_url', InputArgument::REQUIRED, 'Webhook ID or URL.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $webhook_id_or_url = $input->getArgument('webhook_id_or_url');

            if (is_int($webhook_id_or_url) || ctype_digit($webhook_id_or_url)) {
                $webhook = DataObjectPool::get(Webhook::class, (int) $webhook_id_or_url);
            } elseif (filter_var($webhook_id_or_url, FILTER_VALIDATE_URL)) {
                $webhook = Webhooks::findOneBy('url', $webhook_id_or_url);
            } else {
                throw new RuntimeException(
                    sprintf(
                        'Webhook ID or URL required, "%s" given.',
                        $webhook_id_or_url
                    )
                );
            }

            if (!$webhook instanceof Webhook) {
                throw new RuntimeException(sprintf('Webhook "%s" not found.', $webhook_id_or_url));
            }

            return $this->success(
                $this->withSelectedWebhook($webhook, $input),
                $input,
                $output
            );
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }

    abstract protected function withSelectedWebhook(Webhook $webhook, InputInterface $input): string;
}
