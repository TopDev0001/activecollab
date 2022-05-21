<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateMakeClassicADefaultTheme extends AngieModelMigration
{
    public function up()
    {
        if (!$this->isUsingNewTheme()) {
            $this->setConfigOptionValue('theme', 'classic');
        }
    }

    private function isUsingNewTheme(): bool
    {
        return in_array(
            $this->getConfigOptionValue('theme'),
            [
                'indigo',
                'watermelon',
                'classic',
                'neon',
            ]
        );
    }
}
