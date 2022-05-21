<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command\User;

use Angie\Authentication\Repositories\TokensRepository;
use Angie\Command\Command;
use ApiSubscription;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Users;

class IssueTokenCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Issue a token for the given user')
            ->addArgument('email', InputArgument::REQUIRED)
            ->addArgument('client_name', InputArgument::REQUIRED)
            ->addArgument('client_vendor', InputArgument::REQUIRED);
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

            $token = (new TokensRepository())->issueToken(
                $user,
                [
                    'client_name' => $input->getArgument('client_name'),
                    'client_vendor' => $input->getArgument('client_vendor'),
                ]
            );

            if ($token instanceof ApiSubscription) {
                $message = sprintf(
                    'Token <comment>#%s</comment> (<comment>%s</comment>) has been added.',
                    $token->getId(),
                    $token->getFormattedToken()
                );
            } else {
                $message = sprintf('Token <comment>#%s</comment> has been added.', $token->getId());
            }

            return $this->success($message, $input, $output);
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }
}
