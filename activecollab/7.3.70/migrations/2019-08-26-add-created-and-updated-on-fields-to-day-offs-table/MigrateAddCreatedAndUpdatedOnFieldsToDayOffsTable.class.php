<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddCreatedAndUpdatedOnFieldsToDayOffsTable extends AngieModelMigration
{
    public function up()
    {
      if ($this->tableExists('day_offs')) {
          $day_offs = $this->useTableForAlter('day_offs');

          if (!$day_offs->getColumn('created_on')) {
              $day_offs->addColumn(new DBCreatedOnColumn());
              $this->execute('UPDATE ' . $day_offs->getName() . ' SET created_on = NOW()');
          }

          if (!$day_offs->getColumn('updated_on')) {
              $day_offs->addColumn(new DBUpdatedOnColumn());
          }

          $this->doneUsingTables();
      }
    }
}
