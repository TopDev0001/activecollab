<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Update whitelisted HTML editor tags.
 *
 * @package angie.migrations
 */
class MigrateIntroduceWhitelistedTagsConfig extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $config_option_name = 'whitelisted_tags';
        $whitelisted_tags = $this->getConfigOptionValue($config_option_name);
        $whitelisted_tags['visual_editor'] = [
            'p' => ['class', 'style'],
            'img' => ['image-type', 'object-id', 'class'],
            'strike' => ['class', 'style'],
            'span' => ['class', 'data-redactor-inlinemethods', 'data-redactor'],
            'a' => ['class', 'href'],
            'blockquote' => null,
            'br' => null,
            'b' => null, 'strong' => null,
            'i' => null, 'em' => null,
            'u' => null,
        ];
        $this->setConfigOptionValue($config_option_name, $whitelisted_tags);
    }
}
