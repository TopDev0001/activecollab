<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\Links\TextReplacement\Resolver;

use ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\Links\TextReplacement\TextReplacementInterface;
use ActiveCollab\Foundation\Wrappers\ConfigOptions\ConfigOptionsInterface;
use DataObject;
use IComplete;
use Project;
use Task;

class TextReplacementResolver implements TextReplacementResolverInterface
{
    private $config_options;

    public function __construct(ConfigOptionsInterface $config_options)
    {
        $this->config_options = $config_options;
    }

    public function getTextReplacement(
        DataObject $entity,
        string $replacement = TextReplacementInterface::REPLACE_WITH_URL,
        string $suffix = '.'
    ): ?string
    {
        $result = null;
        $result_is_escaped = false;

        if ($replacement === TextReplacementInterface::REPLACE_WITH_NAME) {
            if ($entity instanceof Task && $this->config_options->getValue('show_task_id')) {
                $result = sprintf('#%d: %s%s', $entity->getTaskNumber(), $entity->getName(), $suffix);
            } elseif ($entity instanceof Project && $this->config_options->getValue('show_project_id')) {
                $result = sprintf('#%d: %s%s', $entity->getId(), $entity->getName(), $suffix);
            } elseif (method_exists($entity, 'getName')) {
                $result = sprintf('%s%s', $entity->getName(), $suffix);
            }

            if ($entity instanceof IComplete && $entity->isCompleted()) {
                $result = sprintf('<del>%s</del>', clean($result));
                $result_is_escaped = true;
            }
        } elseif ($replacement === TextReplacementInterface::REPLACE_WITH_URL
            && method_exists($entity, 'getViewUrl')
        ) {
            $result = $entity->getViewUrl();
        }

        if ($result && !$result_is_escaped) {
            $result = clean($result);
        }

        return $result;
    }
}
