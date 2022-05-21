<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Wires;

use ActiveCollab\Module\System\Wires\Base\BaseAvatarProxy;
use WarehouseIntegration;

class AvatarProxy extends BaseAvatarProxy
{
    protected ?int $user_id;
    protected ?string $user_name;
    protected ?string $user_email;

    public function __construct(array $params = null)
    {
        parent::__construct($params);

        $this->user_id = !empty($params['user_id']) ? (int) $params['user_id'] : null;
        $this->user_name = !empty($params['user_name']) ? (string) $params['user_name'] : null;
        $this->user_email = !empty($params['user_email']) ? (string) $params['user_email'] : null;
    }

    protected function getAvatarContext(): string
    {
        return 'user';
    }

    public function execute()
    {
        require_once ANGIE_PATH . '/functions/general.php';
        require_once ANGIE_PATH . '/functions/web.php';
        require_once ANGIE_PATH . '/functions/files.php';

        $connection = $this->getDatabaseConnection();

        if (empty($connection)) {
            $this->renderNaAvatar();

            return;
        }

        if ($this->user_id > 0) {
            $result = $connection->query(
                sprintf(
                    "SELECT `avatar_location`, `first_name`, `last_name`, `email`, `raw_additional_properties` FROM `users` WHERE `id` = '%s'",
                    $connection->real_escape_string((string) $this->user_id)
                )
            );

            if ($result->num_rows > 0) {
                $user_details = $result->fetch_assoc();

                $avatar_location = $user_details['avatar_location'];

                $integration = $connection->query(
                    sprintf(
                        "SELECT `raw_additional_properties` FROM `integrations` WHERE `type` = '%s'",
                        WarehouseIntegration::class
                    )
                );

                $warehouse_integrations = $integration->num_rows
                    ? $integration->fetch_assoc()['raw_additional_properties']
                    : null;

                if ($warehouse_integrations !== null) {
                    $properties = !empty($user_details['raw_additional_properties'])
                        ? unserialize($user_details['raw_additional_properties'])
                        : [];

                    $warehouse_access_token = !empty($warehouse_integrations)
                        ? unserialize($warehouse_integrations)['access_token']
                        : null;

                    $avatar_md5 = $properties['avatar_md5'] ?? null;

                    if (!empty($avatar_location) && !empty($avatar_md5)) {
                        $this->renderAvatarFromWarehouse(
                            $avatar_location,
                            $avatar_md5,
                            $user_details,
                            $warehouse_access_token
                        );
                    } else {
                        $this->makeDefaultAvatar($user_details);
                    }
                } else {
                    $source_file = empty($avatar_location) ? '' : UPLOAD_PATH . '/' . $avatar_location;

                    // user have uploaded avatar, use that avatar
                    if (is_file($source_file)) {
                        $tag = md5($avatar_location);

                        if ($this->getCachedEtag() == $tag) {
                            $this->avatarNotChanged($tag);
                        }

                        $this->renderAvatarFromSource($source_file, $tag);

                        // user doesn't have avatar uploaded generate it
                    } else {
                        $this->makeDefaultAvatar($user_details);
                    }
                }
            } else {
                $this->renderNaAvatar();
            }
        } elseif ($this->user_name || $this->user_email) {
            $this->handleAvatarWithNameOrEmail($this->user_name, $this->user_email);
        } else {
            $this->renderNaAvatar();
        }
    }

    /**
     * Handle avatar with user name or email.
     *
     * @param $name
     * @param $email
     */
    private function handleAvatarWithNameOrEmail($name, $email)
    {
        $user_details = [
            'first_name' => $name,
            'last_name' => null,
            'email' => $email,
        ];

        if ($name && $email) {
            // get appropriate image name for the fake user
            $image_tag = $this->getTagFromNameAndEmail($name, $email);
            $source_file = sprintf(
                '%s/modules/system/resources/sample_projects/avatars/%s.png',
                APPLICATION_PATH,
                $image_tag
            );

            if (is_file($source_file)) {
                $tag = $this->getTagFromSourceFile($source_file);

                $this->renderAvatarFromSource($source_file, $tag);
            } else {
                $this->makeDefaultAvatar($user_details);
            }
        } elseif ($name || $email) {
            $this->makeDefaultAvatar($user_details);
        } else {
            $this->renderNaAvatar();
        }
    }

    private function getTagFromNameAndEmail(string $user_name, string $user_email): string
    {
        return md5($user_name . $user_email);
    }

    private function makeDefaultAvatar(array $user_details): void
    {
        $tag = $this->getDefaultAvatarTag(
            $user_details['first_name'],
            $user_details['last_name'],
            $user_details['email']
        );

        if ($this->getCachedEtag() == $tag) {
            $this->avatarNotChanged($tag);
        }

        $this->renderDefaultAvatar(
            $user_details['first_name'],
            $user_details['last_name'],
            $user_details['email'] ?? ''
        );
    }

    protected function renderNaAvatar(): void
    {
        $this->renderAvatarFromText(
            'NA',
            $this->getDefaultAvatarTag(
                '',
                '',
                'not.available@example.com'
            )
        );
    }

    private function renderDefaultAvatar(
        ?string $first_name,
        ?string $last_name,
        string $email
    ): void
    {
        $this->renderAvatarFromText(
            $this->getTextToRender(
                $first_name,
                $last_name,
                $email,
            ),
            $this->getDefaultAvatarTag(
                $first_name,
                $last_name,
                $email,
            )
        );
    }

    private function getTextToRender(
        ?string $first_name,
        ?string $last_name,
        string $email
    ): string
    {
        $text = '';

        if ($first_name || $last_name) {
            if ($first_name) {
                $text .= mb_substr($first_name, 0, 1);
            }

            if ($last_name) {
                $text .= mb_substr($last_name, 0, 1);
            }
        } else {
            $email_username = explode('@', $email)[0];
            $email_username_parts = explode('.', $email_username);

            foreach ($email_username_parts as $email_username_part) {
                $text .= mb_substr($email_username_part, 0, 1);
            }
        }

        return $text;
    }

    private function getDefaultAvatarTag(
        ?string $first_name,
        ?string $last_name,
        ?string $email
    ): string
    {
        return md5($first_name . $last_name . $email);
    }

    /**
     * Render warehouse avatar.
     */
    private function renderAvatarFromWarehouse(
        string $location,
        ?string $hash,
        array $user_details,
        ?string $access_token
    ): void
    {
        if ($this->getCachedEtag() == $hash) {
            $this->avatarNotChanged($hash);
        }

        $downloaded_avatar = $this->downloadAvatarFromWarehouse($location, $hash, $access_token);

        if ($downloaded_avatar) {
            $this->renderAvatarFromSource($downloaded_avatar, $hash);
        } else {
            $this->makeDefaultAvatar($user_details);
        }
    }

    private function downloadAvatarFromWarehouse($location, $hash, $access_token): ?string
    {
        $location = urlencode($location);

        $source_file = CACHE_PATH . "/avatar-{$location}-{$hash}";
        if ($this->isDowloadedWarehouseFile($source_file)) {
            return $source_file;
        } else {
            $this->downloadWarehouseFile(
                $location,
                $hash,
                $access_token,
                $source_file
            );

            if ($this->isDowloadedWarehouseFile($source_file)) {
                return $source_file;
            }

            if (is_file($source_file)) {
                @unlink($source_file);
            }

            return null;
        }
    }

    private function isDowloadedWarehouseFile(string $source_path): bool
    {
        return is_file($source_path) && filesize($source_path);
    }
}
