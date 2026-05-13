<?php

/**
 * Create Nag base tables (as of Nag 2.x).
 *
 * Copyright 2010-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class NagBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('nag_tasks', $tableList)) {
            $t = $this->createTable('nag_tasks', ['autoincrementKey' => false]);
            $t->column('task_id', 'string', ['limit' => 32, 'null' => false]);
            $t->column('task_owner', 'string', ['null' => false]);
            $t->column('task_creator', 'string', ['null' => false]);
            $t->column('task_parent', 'string');
            $t->column('task_assignee', 'string');
            $t->column('task_name', 'string', ['null' => false]);
            $t->column('task_uid', 'string', ['null' => false]);
            $t->column('task_desc', 'text');
            $t->column('task_start', 'integer');
            $t->column('task_due', 'integer');
            $t->column('task_priority', 'integer', ['default' => 0, 'null' => false]);
            $t->column('task_estimate', 'float');
            $t->column('task_category', 'string', ['limit' => 80]);
            $t->column('task_completed', 'integer', ['limit' => 1, 'default' => 0, 'null' => false]);
            $t->column('task_completed_date', 'integer');
            $t->column('task_alarm', 'integer', ['default' => 0, 'null' => false]);
            $t->column('task_alarm_methods', 'text');
            $t->column('task_private', 'integer', ['limit' => 1, 'default' => 0, 'null' => false]);
            $t->primaryKey(['task_id']);
            $t->end();

            $this->addIndex('nag_tasks', ['task_owner']);
            $this->addIndex('nag_tasks', ['task_uid']);
            $this->addIndex('nag_tasks', ['task_start']);
        }

        if (!in_array('nag_shares', $tableList)) {
            $t = $this->createTable('nag_shares', ['autoincrementKey' => false]);
            $t->column('share_id', 'integer', ['null' => false]);
            $t->column('share_name', 'string', ['null' => false]);
            $t->column('share_owner', 'string');
            $t->column('share_flags', 'integer', ['limit' => 2, 'default' => 0, 'null' => false]);
            $t->column('perm_creator', 'integer', ['limit' => 2, 'default' => 0, 'null' => false]);
            $t->column('perm_default', 'integer', ['limit' => 2, 'default' => 0, 'null' => false]);
            $t->column('perm_guest', 'integer', ['limit' => 2, 'default' => 0, 'null' => false]);
            $t->column('attribute_name', 'string', ['null' => false]);
            $t->column('attribute_desc', 'string');
            $t->column('attribute_color', 'string', ['limit' => 7]);
            $t->primaryKey(['share_id']);
            $t->end();

            $this->addIndex('nag_shares', ['share_name']);
            $this->addIndex('nag_shares', ['share_owner']);
            $this->addIndex('nag_shares', ['perm_creator']);
            $this->addIndex('nag_shares', ['perm_default']);
            $this->addIndex('nag_shares', ['perm_guest']);
        }

        if (!in_array('nag_shares_groups', $tableList)) {
            $t = $this->createTable('nag_shares_groups', ['autoincrementKey' => false]);
            $t->column('share_id', 'integer', ['null' => false]);
            $t->column('group_uid', 'string', ['null' => false]);
            $t->column('perm', 'integer', ['limit' => 2, 'null' => false]);
            $t->end();

            $this->addIndex('nag_shares_groups', ['share_id']);
            $this->addIndex('nag_shares_groups', ['group_uid']);
            $this->addIndex('nag_shares_groups', ['perm']);
        }

        if (!in_array('nag_shares_users', $tableList)) {
            $t = $this->createTable('nag_shares_users', ['autoincrementKey' => false]);
            $t->column('share_id', 'integer', ['null' => false]);
            $t->column('user_uid', 'string', ['limit' => 255, 'null' => false]);
            $t->column('perm', 'integer', ['limit' => 2, 'null' => false]);
            $t->end();

            $this->addIndex('nag_shares_users', ['share_id']);
            $this->addIndex('nag_shares_users', ['user_uid']);
            $this->addIndex('nag_shares_users', ['perm']);
        }
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        $this->dropTable('nag_tasks');
        $this->dropTable('nag_shares');
        $this->dropTable('nag_shares_groups');
        $this->dropTable('nag_shares_users');
    }
}
