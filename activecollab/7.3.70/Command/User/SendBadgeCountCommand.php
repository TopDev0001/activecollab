<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Command\User;

use ActiveCollab\Module\System\Utils\UserBadgeCountNotifier\UserBadgeCountNotifierInterface;
use AngieApplication;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use User;
use Users;

class SendBadgeCountCommand extends UserCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Send badge count for user.')
            ->addArgument('user_id', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user_id = (int) $input->getArgument('user_id');

        try {
            /** @var User $user */
            $user = Users::findById($user_id);

            if (!$user->isActive()) {
                return $this->abort(
                    'User is not active.',
                    255,
                    $input,
                    $output
                );
            }

            AngieApplication::getContainer()
                ->get(UserBadgeCountNotifierInterface::class)
                ->notify($user);

            return $this->success(
                sprintf('Silent push notification for user id: %s has been sent.', $user_id),
                $input,
                $output
            );
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Failed to send silent push notification for user.',
                [
                    'error' => $e->getMessage(),
                    'user_id' => $user_id,
                ]
            );

            return $this->abortDueToException($e, $input, $output);
        }
    }
}
