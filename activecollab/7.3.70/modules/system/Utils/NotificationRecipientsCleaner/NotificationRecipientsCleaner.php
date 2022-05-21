<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\NotificationRecipientsCleaner;

use DataObject;
use DB;
use IProjectElement;
use NotificationRecipients;
use Users;

class NotificationRecipientsCleaner implements NotificationRecipientsCleanerInterface
{
    public function clean(DataObject $projectElement): void
    {
        if ($projectElement instanceof IProjectElement) {
            $query = '
                SELECT DISTINCT(nr.recipient_id)
                FROM notifications AS n
                INNER JOIN notification_recipients AS nr ON n.id = nr.notification_id
                WHERE n.parent_type = ? AND n.parent_id = ?';

            $recipients = DB::executeFirstColumn($query, get_class($projectElement), $projectElement->getId());

            if ($recipients) {
                $project = $projectElement->getProject();
                $users = Users::findByIds($recipients);
                foreach ($users as $user) {
                    if (!$project->canView($user)) {
                        $query = '
                            SELECT nr.id
                            FROM notifications AS n
                            INNER JOIN notification_recipients AS nr ON n.id = nr.notification_id
                            WHERE n.parent_type = ? AND n.parent_id = ? AND nr.recipient_id = ?';
                        $notification_recipient_ids = DB::executeFirstColumn($query, get_class($projectElement), $projectElement->getId(), $user->getId());
                        if (is_array($notification_recipient_ids)) {
                            NotificationRecipients::deleteBy([], [], $notification_recipient_ids);
                        }
                    }
                }
            }
        }
    }
}
