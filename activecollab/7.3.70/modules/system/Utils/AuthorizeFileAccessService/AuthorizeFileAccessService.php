<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\AuthorizeFileAccessService;

use ActiveCollab\DateValue\DateTimeValue;
use ActiveCollab\Foundation\Urls\Factory\UrlFactory;
use ActiveCollab\Foundation\Urls\Services\WarehouseUrlInterface;
use ActiveCollab\Module\System\Utils\JwtTokenIssuer\JwtTokenIssuerInterface;
use Attachment;
use Exception;
use File;
use IFile;
use LogicException;
use Thumbnails;
use User;

class AuthorizeFileAccessService implements AuthorizeFileAccessServiceInterface
{
    private JwtTokenIssuerInterface $token_issuer;
    private UrlFactory $url_factory;

    public function __construct(
        JwtTokenIssuerInterface $token_issuer,
        UrlFactory $url_factory
    ) {
        $this->token_issuer = $token_issuer;
        $this->url_factory = $url_factory;
    }

    /**
     * @param  IFile|File|Attachment $file
     * @throws Exception
     */
    public function authorize(
        IFile $file,
        string $intent,
        User $user,
        bool $force = false,
        ?int $width = null,
        ?int $height = null,
        ?string $scale = null
    ): string
    {
        if (!in_array($intent, JwtTokenIssuerInterface::INTENTS)) {
            throw new Exception('Not a valid intent');
        }

        if (!$file->canView($user)) {
            throw new LogicException('Permission denied.');
        }

        $token = $this
            ->token_issuer
            ->issueForIntent(
                $intent,
                $user
            )
            ->toString();

        return $this->getUrl(
            $intent,
            $file,
            $token,
            $force,
            $width,
            $height,
            $scale
        );
    }

    private function getUrl(
        string $intent,
        IFile $file,
        string $token,
        bool $force,
        ?int $width,
        ?int $height,
        ?string $scale
    ) {
        switch ($intent) {
            case JwtTokenIssuerInterface::INTENT_PREVIEW:
                $placeholder = WarehouseUrlInterface::FILE_EXTENSION_PREVIEW;
                $url = $file->getPreviewUrl();
                break;
            case JwtTokenIssuerInterface::INTENT_DOWNLOAD:
                $placeholder = WarehouseUrlInterface::FILE_EXTENSION_DOWNLOAD;
                $url = $file->getDownloadUrl($force);
                break;
            case JwtTokenIssuerInterface::INTENT_THUMBNAIL:
                $placeholder = WarehouseUrlInterface::FILE_EXTENSION_THUMBNAILS;
                $url = $file->getThumbnailUrl($width ?: 80, $height ?: 80, $scale ?: Thumbnails::SCALE);
                break;
            default:
                throw new Exception('Unhandled intent');
        }

        $return_url = str_replace($placeholder, $token, $url);

        return $this
            ->url_factory
            ->createFromUrl($return_url)
            ->getExtendedUrl(
                [
                    'authorized_at' => (new DateTimeValue())->getTimestamp(),
                ]
            );
    }
}
