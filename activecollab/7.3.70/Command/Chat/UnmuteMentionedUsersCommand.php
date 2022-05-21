<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Command\Chat;

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ConversationUserEvents\ConversationUserUpdatedEvent;
use AngieApplication;
use ConversationUsers;
use DataObjectPool;
use DB;
use Exception;
use Message;
use Messages;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnmuteMentionedUsersCommand extends ChatCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Unmute mentioned user conversation in conversation.')
            ->addArgument('message_id', InputArgument::REQUIRED)
            ->addArgument(
                'mentioned_user_ids',
                InputArgument::REQUIRED,
                'Ids of mentioned users in conversation'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $message_id = (int) $input->getArgument('message_id');
        $user_ids = (string) $input->getArgument('mentioned_user_ids');

        $user_ids = explode(' ', $user_ids);

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

            $muted_conversation_users_ids = DB::executeFirstColumn(
                'SELECT id FROM conversation_users WHERE conversation_id = ? AND is_muted = ? AND user_id IN (?)',
                $message->getConversationId(),
                true,
                $user_ids
            );

            if (empty($muted_conversation_users_ids)) {
                return $this->success(
                    'There is no muted conversations for users.',
                    $input,
                    $output
                );
            }

            DB::execute(
                'UPDATE conversation_users SET is_muted = ?, is_original_muted = ? WHERE id IN (?)',
                false,
                true,
                $muted_conversation_users_ids
            );

            ConversationUsers::clearCacheFor($muted_conversation_users_ids);

            $conversation_users = ConversationUsers::findByIds($muted_conversation_users_ids);

            foreach ($conversation_users as $user_conversation) {
                DataObjectPool::announce(new ConversationUserUpdatedEvent($user_conversation));
            }

            return $this->success(
                'Mentioned users has been successfully unmuted.',
                $input,
                $output
            );
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Failed to unmute mentioned users.',
                [
                    'users' => $user_ids,
                    'error' => $e->getMessage(),
                ]
            );

            return $this->abortDueToException($e, $input, $output);
        }
    }
}
