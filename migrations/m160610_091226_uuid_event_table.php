<?php

use yii\db\Migration;

class m160610_091226_uuid_event_table extends Migration
{
    public function up()
    {
        if(!$this->isMySQL()) {
            throw new \Exception('Migration currently is supporting only MySQL table scheme.');
        }

        $this->execute("
            CREATE TABLE `uuid_event` (
              `id` BIGINT(22) UNSIGNED NOT NULL AUTO_INCREMENT,
              `uuid` BIGINT(22) UNSIGNED NOT NULL,
              `action` CHAR(25) NOT NULL,
              `target` BIGINT(22) NULL,
              `value` TEXT NULL,
              `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              INDEX `I_uuid_action_target` USING BTREE (`uuid` ASC, `action` ASC, `target` ASC))
            ENGINE = MyISAM
            DEFAULT CHARACTER SET = utf8
            COLLATE = utf8_general_ci;
        ");
    }

    protected function isMySQL()
    {
        return $this->db->driverName === 'mysql';
    }

    public function down()
    {
        echo "m160610_091226_uuid_event_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
