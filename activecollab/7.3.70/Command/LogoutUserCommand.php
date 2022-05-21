<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command;

use Angie\Command\Command;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Users;
use UserSessions;

/**
 * @package ActiveCollab\Command
 */
class LogoutUserCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Logout user')
            ->addArgument('id', InputArgument::REQUIRED, 'User ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $id = $input->getArgument('id');

            if (!filter_var($id, FILTER_VALIDATE_INT)) {
                return $this->abort('ID must be value of integer', 1, $input, $output);
            }

            $user = Users::findById($id);

            if (!$user) {
                return $this->success('User does not exists', $input, $output);
            }

            UserSessions::terminateUserSessions($user);

            return $this->success('Done', $input, $output);
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }
}
