<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddSummaryLengthToTimeRecords extends AngieModelMigration
{
    public function up()
    {
        $this->execute('ALTER TABLE `time_records` ADD `summary_length` ENUM("empty", "short", "long") NOT NULL DEFAULT "empty" AFTER `summary`');

        $this->execute('UPDATE `time_records` SET `summary_length` = "long" WHERE CHAR_LENGTH(`summary`) > 100');
        $this->execute(
            'UPDATE `time_records` SET `summary_length` = "short" WHERE `summary_length` = "empty" AND (`summary` IS NOT NULL AND `summary` != "")'
        );

        $this->execute('CREATE TRIGGER summary_length_for_time_records_before_insert BEFORE INSERT ON `time_records` FOR EACH ROW
            BEGIN
                IF NEW.`summary` IS NULL OR NEW.`summary` = "" THEN
                    SET NEW.`summary_length` = "empty";
                ELSE
                    IF CHAR_LENGTH(NEW.`summary`) > 100 THEN
                        SET NEW.`summary_length` = "long";
                    ELSE
                        SET NEW.`summary_length` = "short";
                    END IF;
                END IF;
            END'
        );

        $this->execute('CREATE TRIGGER summary_length_for_time_records_before_update BEFORE UPDATE ON `time_records` FOR EACH ROW
            BEGIN
                IF NEW.`summary` = OLD.`summary` THEN
                    SET NEW.`summary_length` = OLD.`summary_length`;
                ELSE
                    IF NEW.`summary` IS NULL OR NEW.`summary` = "" THEN
                        SET NEW.`summary_length` = "empty";
                    ELSE
                        IF CHAR_LENGTH(NEW.`summary`) > 100 THEN
                            SET NEW.`summary_length` = "long";
                        ELSE
                            SET NEW.`summary_length` = "short";
                        END IF;
                    END IF;
                END IF;
            END'
        );
    }
}
