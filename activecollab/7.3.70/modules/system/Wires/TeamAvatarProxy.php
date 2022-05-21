<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Wires;

use ActiveCollab\Module\System\Wires\Base\BaseAvatarProxy;

class TeamAvatarProxy extends BaseAvatarProxy
{
    private ?int $team_id;
    private ?string $team_name;

    public function __construct(array $params = null)
    {
        parent::__construct($params);

        $this->team_id = !empty($params['team_id']) ? (int) $params['team_id'] : null;
        $this->team_name = !empty($params['team_name']) ? (string) $params['team_name'] : null;
    }

    protected function getAvatarContext(): string
    {
        return 'team';
    }

    public function execute()
    {
        require_once ANGIE_PATH . '/functions/general.php';
        require_once ANGIE_PATH . '/functions/web.php';
        require_once ANGIE_PATH . '/functions/files.php';

        $this->renderDefaultAvatar();
    }

    private function renderDefaultAvatar(): void
    {
        $this->renderAvatarFromText(
            $this->getTextToRender(),
            md5((string) $this->team_name)
        );
    }

    private function getTextToRender(): string
    {
        $bits = explode(' ', (string) $this->team_name);

        switch (count($bits)) {
            case 0:
                return self::DEFAULT_TEXT;
            case 1:
                return mb_substr($bits[0], 0, 2);
            default:
                return mb_substr($bits[0], 0, 1) . mb_substr($bits[1], 0, 1);
        }
    }
}
