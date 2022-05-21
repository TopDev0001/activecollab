<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Command\User;

use ActiveCollab\Module\System\Services\Conversation\NotifyUserAboutUnreadMessagesServiceInterface;
use AngieApplication;
use DateTimeValue;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Users;

class NotifyUserAboutUnreadMessagesCommand extends UserCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Send email to notify user about unread messages.')
            ->addArgument('user_id', InputArgument::REQUIRED)
            ->addArgument(
                'current_time',
                InputArgument::OPTIONAL,
                "Override system current time. Example: 'YYYY-MM-DD h:i:s'."
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user_id = (int) $input->getArgument('user_id');
        $current_time = new DateTimeValue($input->getArgument('current_time') ?: null);

        try {
            $user = Users::findById($user_id);

            if (!$user) {
                return $this->abort(
                    sprintf('User with id <command>%s</command> not found.', $user_id),
                    1,
                    $input,
                    $output
                );
            }

            AngieApplication::getContainer()
                ->get(NotifyUserAboutUnreadMessagesServiceInterface::class)
                ->notify($user, $current_time);

            return $this->success('Email notification has been successfully sent to user.', $input, $output);
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Failed to send email notification about unread messages.',
                [
                    'user_id' => $user_id,
                    'error' => $e->getMessage(),
                ]
            );

            return $this->abortDueToException($e, $input, $output);
        }
    }
}
