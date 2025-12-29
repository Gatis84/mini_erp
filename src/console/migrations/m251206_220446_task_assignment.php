<?php

use yii\db\Migration;

class m251206_220446_task_assignment extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%task_assignment}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull(),
            'employee_id' => $this->integer()->notNull(),
            'assigned_at' => $this->dateTime()->defaultExpression('GETDATE()'),
            'planned_start_at' => $this->dateTime(),
            'planned_end_at' => $this->dateTime(),
            'completed_at' => $this->dateTime(),
            'status' => $this->smallInteger()->defaultValue(0),
        ]);

        $this->addForeignKey(
            'fk-ta-task_id',
            '{{%task_assignment}}','task_id',
            '{{%task}}','id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-ta-employee_id',
            '{{%task_assignment}}','employee_id',
            '{{%employee}}','id',
            'CASCADE'
        );

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%task_assignment}}');

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251206_220446_task_assignment cannot be reverted.\n";

        return false;
    }
    */
}
