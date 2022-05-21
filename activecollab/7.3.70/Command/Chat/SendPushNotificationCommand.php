<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Command\Chat;

use ActiveCollab\Module\System\Services\Message\UserMessage\PushNotificationUserMessageServiceInterface;
use AngieApplication;
use Exception;
use Message;
use Messages;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendPushNotificationCommand extends ChatCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Send push notifications for message.')
            ->addArgument('message_id', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $message_id = (int) $input->getArgument('message_id');

        try {
            $message = Messages::findById($message_id);

            if (!$message instanceof Message) {
                return $this->abort(
                    'Message not exist.',
                    255,
                    $input,
                    $output
                );
            }

            AngieApplication::getContainer()
                ->get(PushNotificationUserMessageServiceInterface::class)
                ->send($message);

            return $this->success(
                sprintf('Sent push notification for message id: %s', $message_id),
                $input,
                $output
            );
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Failed to send push notification for chat.',
                [
                    'error' => $e->getMessage(),
                ]
            );

            return $this->abortDueToException($e, $input, $output);
        }
    }
}
