<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command\User;

use Angie\Command\Command;
use AngieApplication;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Users;

class SetPasswordCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Set a password for the given user')
            ->addArgument('email', InputArgument::REQUIRED)
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED);
    }

    protected function getCommandNamePrefix(): string
    {
        return parent::getCommandNamePrefix() . 'user:';
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $email_address = $input->getArgument('email');

            $user = Users::findByEmail($email_address);

            if (!$user) {
                return $this->abort(
                    sprintf('User with address <command>%s</command> not found.', $email_address),
                    1,
                    $input,
                    $output
                );
            }

            $password = $input->getOption('password');

            if (empty($password)) {
                if ($input->isInteractive()) {
                    $password = $this
                        ->getHelper('question')
                        ->ask(
                            $input,
                            $output,
                            (new Question('Enter password: '))
                                ->setHidden(true)
                                ->setHiddenFallback(false)
                        );

                    if (empty($password)) {
                        return $this->abort('Password is required', 1, $input, $output);
                    }
                } else {
                    return $this->abort('Password is required', 1, $input, $output);
                }
            }

            $user->forceChangePassword(
                $user,
                $password,
                $password,
                null,
                false
            );

            AngieApplication::cache()->clear();

            return $this->success(
                sprintf('Done. Password for user <comment>%s</comment> has been changed.', $user->getEmail()),
                $input,
                $output
            );
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }
}
