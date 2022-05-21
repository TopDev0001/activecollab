<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Utils\JwtTokenIssuer\JwtTokenIssuerInterface;

function smarty_function_notification_attachments_table(array $params): string
{
    $object = array_required_var($params, 'object', false, ApplicationObject::class);
    $recipient = array_required_var($params, 'recipient');

    if ($object instanceof IAttachments) {
        if ($attachments = $object->getAttachments()) {
            $content = "<table width='100%' id='attachment'><tbody><tr><td style='padding-bottom:15px;'>";

            /** @var Attachment|RemoteAttachment $attachment */
            foreach ($attachments as $attachment) {
                $jwt_token_issuer = AngieApplication::getContainer()->get(JwtTokenIssuerInterface::class);
                $jwt = $jwt_token_issuer->issueDownloadToken($recipient);

                $url = str_replace_utf('--DOWNLOAD-TOKEN--', $jwt, $attachment->getPublicDownloadUrl(true));

                $content .= "&#128206; <a href='" . clean($url) . "'>" . clean($attachment->getName()) . '</a>';

                if ($attachment instanceof LocalAttachment || $attachment instanceof WarehouseAttachment) {
                    $content .= " <span style='color:#91918D;'>" . format_file_size($attachment->getSize()) . '</span>';
                } elseif ($attachment instanceof GoogleDriveAttachment) {
                    $content .= " <span style='color:#91918D; font-style: italic;'>(Google Drive)</span>";
                } elseif ($attachment instanceof DropboxAttachment) {
                    $content .= " <span style='color:#91918D; font-style: italic;'>(Dropbox)</span>";
                }

                $content .= '<br>';
            }

            return "$content</td></tr></tbody></table>";
        }
    }

    return '';
}
