<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%employee}}`.
 */
class m251204_113954_create_employee_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%employee}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'first_name' => $this->string(100)->notNull(),
            'last_name' => $this->string(100)->notNull(),
            'birth_date' => $this->date()->null(),
            'access_level' => $this->integer()->notNull(),
            'role' => $this->string(50)->notNull(), // admin, teamLead, employee
            // 'status' => $this->smallInteger()->defaultValue(1), // active/inactive
            'created_at' =>  $this->dateTime()->defaultExpression('GETDATE()'),
            'updated_at' =>  $this->dateTime()->defaultExpression('GETDATE()'),
            ]);

        $this->addForeignKey(
        'fk-employee-user_id',
        '{{%employee}}','user_id',
        '{{%user}}','id',
        'SET NULL','CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%employee}}');
    }
}
