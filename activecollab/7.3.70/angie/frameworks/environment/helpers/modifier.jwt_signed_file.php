<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Utils\JwtTokenIssuer\JwtTokenIssuerInterface;

/**
 * @param  User|null            $user
 * @param  bool                 $is_short
 * @return string
 * @throws InvalidInstanceError
 */
function smarty_modifier_jwt_signed_file(string $content, $user = null)
{
    if ($user === null) {
        $user = AngieApplication::authentication()->getAuthenticatedUser();
    }

    $jwt_token_issuer = AngieApplication::getContainer()->get(JwtTokenIssuerInterface::class);

    $jwt_preview = $jwt_token_issuer->issuePreviewToken($user);
    $jwt_thumbnail = $jwt_token_issuer->issueThumbnailToken($user);
    $jwt_download = $jwt_token_issuer->issueDownloadToken($user);

    $content = str_replace_utf('--THUMBNAIL-TOKEN--', $jwt_thumbnail, $content);
    $content = str_replace_utf('--PREVIEW-TOKEN--', $jwt_preview, $content);
    $content = str_replace_utf('--DOWNLOAD-TOKEN--', $jwt_download, $content);

    return $content;
}
