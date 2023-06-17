<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%queue_failed}}`.
 */
class m230617_125215_add_queue_failed_job_id_column extends Migration
{
    /**yii
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%queue_failed}}', 'original_job_id', $this->string()->after('class'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%queue_failed}}', 'original_job_id');
    }
}
