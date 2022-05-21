<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Command\Chat;

use ActiveCollab\Module\System\Model\Conversation\ConversationInterface;
use ActiveCollab\Module\System\Utils\UsersBadgeCountThrottler\UsersBadgeCountThrottlerInterface;
use AngieApplication;
use Conversation;
use Conversations;
use Exception;
use IMembers;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendBadgeCountForConversationUsersCommand extends ChatCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Send badge count for conversation users.')
            ->addArgument('conversation_id', InputArgument::REQUIRED)
            ->addArgument('message_creator_id', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conversation_id = (int) $input->getArgument('conversation_id');
        $message_creator_id = (int) $input->getArgument('message_creator_id');

        try {
            /** @var Conversation|IMembers $conversation */
            $conversation = Conversations::findById($conversation_id);

            if (!$conversation) {
                return $this->abort(
                    'Conversation does not exist.',
                    255,
                    $input,
                    $output
                );
            }

            AngieApplication::getContainer()
                ->get(UsersBadgeCountThrottlerInterface::class)
                ->throttle(
                    $this->ignoreMessageCreator(
                        $conversation,
                        $message_creator_id
                    )
                );

            return $this->success(
                sprintf('Badge count for conversation #%s has been sent', $conversation_id),
                $input,
                $output
            );
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Failed to send badge count to conversation users.',
                [
                    'error' => $e->getMessage(),
                ]
            );

            return $this->abortDueToException($e, $input, $output);
        }
    }

    private function ignoreMessageCreator(ConversationInterface $conversation, int $message_creator_id): array
    {
        return array_diff($conversation->getMemberIds(), [$message_creator_id]);
    }
}
