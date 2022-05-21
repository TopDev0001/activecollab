<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateUpdateLabelColors extends AngieModelMigration
{
    public function up()
    {
        $color_map = [
            '#FFFF00' => '#FDF196',
            '#00A651' => '#C3E799',
            '#FF0000' => '#FF9C9C',
            '#0000FF' => '#BEACF9',
            '#00B25C' => '#C3E799',
            '#F26522' => '#FBBB75',
            '#ACACAC' => '#DDDDDD',
            '#4096DB' => '#A0CBFD',
            '#15CAA1' => '#BDF7FD',
            '#E85287' => '#FBD6E7',
            '#31BFFF' => '#BEEAFF',
            '#A38670' => '#EAC2AD',
            '#F56F72' => '#FBD6E7',
            '#777777' => '#DDDDDD',
            '#777' => '#DDDDDD',
            '#999999' => '#DDDDDD',
            '#999' => '#DDDDDD',
            '#8F4775' => '#C49CB6',
            '#FB7C0D' => '#FBBB75',
            '#021CC9' => '#BEACF9',
            '#309AE8' => '#BEEAFF',
            '#E64745' => '#FF9C9C',
            '#E2AE00' => '#FDF196',
            '#177AB0' => '#A0CBFD',
            '#333333' => '#CACACA',
            '#FF79AA' => '#FBD6E7',
            '#D575B6' => '#FBD6E7',
            '#165F9F' => '#A0CBFD',
            '#E24272' => '#FBD6E7',
            '#9CAD01' => '#98B57C',
            '#CF6D2E' => '#EAC2AD',
            '#5A9721' => '#98B57C',
            '#00ABA2' => '#B9E4E0',
            '#000000' => '#CACACA',
            '#000' => '#CACACA',
            '#00B0A7' => '#B9E4E0',
            '#478500' => '#98B57C',
            '#488BD5' => '#A0CBFD',
            '#70D8E4' => '#B9E4E0',
            '#A30000' => '#FF9C9C',
            '#AA75AA' => '#FBD6E7',
        ];

        $this->transact(
            function () use ($color_map) {
                foreach ($color_map as $old_color => $new_color) {
                    $this->execute('UPDATE `labels` SET `color` = ? WHERE `color` = ?', $new_color, $old_color);
                }

                $this->execute('UPDATE `labels` SET `color` = ? WHERE `color` NOT IN (?)', '#DDDDDD', array_values($color_map));
            }
        );
    }
}
