<?php

use yii\db\Migration;

class m160610_091326_uuid_param_table extends Migration
{
    public function up()
    {
        if(!$this->isMySQL()) {
            throw new \Exception('Migration currently is supporting only MySQL table scheme.');
        }

        $this->execute("
            CREATE TABLE `uuid_param` (
              `uuid` BIGINT(22) UNSIGNED NOT NULL,
              `param` CHAR(25) NOT NULL,
              `value` TEXT NULL,
              `datetime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`uuid`, `param`))
            ENGINE = InnoDB
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
        echo "m160610_091326_uuid_param_table cannot be reverted.\n";

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
