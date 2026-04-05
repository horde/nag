<?php
/**
 * Adds autoincrement flags
 *
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class NagUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('nag_shares', 'share_id', 'autoincrementKey');
        if (in_array('nag_shares_seq', $this->tables())) {
            $this->dropTable('nag_shares_seq');
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('nag_shares', 'share_id', 'integer', array('null' => false, 'autoincrement' => false));
    }

}