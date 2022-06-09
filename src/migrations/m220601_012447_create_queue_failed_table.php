<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%queue_failed}}`.
 */
class m220601_012447_create_queue_failed_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $text = $this->text()->notNull();
        if ($this->db->driverName === 'mysql') {
            $text = 'LONGTEXT NOT NULL';
        }
        $this->createTable('{{%queue_failed}}', [
            'id' => $this->primaryKey(),
            'queue' => $this->string()->notNull(),
            'class' => $this->string()->notNull(),
            'job' => $text,
            'error' => $text,
            'failed_at' => $this->integer()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%queue_failed}}');
    }
}
