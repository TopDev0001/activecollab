<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Command\User;

use ActiveCollab\Module\System\Model\Conversation\GroupConversation;
use ActiveCollab\Module\System\Utils\Conversations\GroupConversationAdminGeneratorInterface;
use AngieApplication;
use Conversations;
use DB;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Users;

class ConversationsLastAdminCommand extends UserCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Assign new admin for conversations where user was a last admin.')
            ->addArgument('user_id', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user_id = $input->getArgument('user_id');

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

            if ($user->isActive()) {
                return $this->abort(
                    'Command can be executed only for archived or trashed user.',
                    1,
                    $input,
                    $output
                );
            }

            $conversation_ids = DB::executeFirstColumn(
                'SELECT DISTINCT cu.conversation_id
                    FROM conversation_users cu
                        LEFT JOIN conversations c ON c.id = cu.conversation_id
                            WHERE c.type = ? AND cu.user_id = ? AND cu.is_admin = ?',
                GroupConversation::class,
                $user->getId(),
                true
            );

            if (!empty($conversation_ids)) {
                /** @var $conversations */
                $conversations = Conversations::findByIds($conversation_ids);

                foreach ($conversations as $conversation) {
                    AngieApplication::getContainer()
                        ->get(GroupConversationAdminGeneratorInterface::class)
                        ->generate($conversation, [$user->getId()]);
                }

                $message = sprintf(
                    'Successfully generate new admin for %s conversations.',
                    count($conversation_ids)
                );
            } else {
                $message = 'There are no user conversations to generate new admin.';
            }

            AngieApplication::log()->info($message, ['user_id' => $user->getId()]);

            return $this->success($message, $input, $output);
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Failed to generate new conversations admins.',
                [
                    'user_id' => $user_id,
                    'error' => $e->getMessage(),
                ]
            );

            return $this->abortDueToException($e, $input, $output);
        }
    }
}
