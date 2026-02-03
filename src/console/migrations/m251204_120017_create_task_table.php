<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%task}}`.
 */
class m251204_120017_create_task_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%task}}', [
            'id' => $this->primaryKey(),
            'construction_site_id' => $this->integer()->notNull(),
            'title' => $this->string(128)->notNull(),
            'description' => $this->text(),
            'status' => $this->smallInteger()->defaultValue(0),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->dateTime()->defaultExpression('GETDATE()'),
            'updated_at' =>  $this->dateTime()->defaultExpression('GETDATE()'),
            'planned_start_at' => $this->dateTime(),
            'planned_end_at' => $this->dateTime(),
            'completed_at' => $this->dateTime(),
        ]);

        $this->addForeignKey(
        'fk-task-site_id',
        '{{%task}}','construction_site_id',
        '{{%construction_site}}','id',
        'CASCADE'
        );

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%task}}');
    }
}
