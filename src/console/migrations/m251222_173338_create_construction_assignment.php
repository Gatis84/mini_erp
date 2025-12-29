<?php

use yii\db\Migration;

class m251222_173338_create_construction_assignment extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%construction_assignment}}', [
            'id' => $this->primaryKey(),
            'construction_site_id' => $this->integer()->notNull(),
            'employee_id' => $this->integer()->notNull(),
            'assigned_at' => $this->dateTime()->defaultExpression('GETDATE()'),
            'completed_at' => $this->dateTime(),
        ]);

        $this->addForeignKey(
            'fk-ca-construction_site_id',
            '{{%construction_assignment}}','construction_site_id',
            '{{%construction_site}}','id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-ca-employee_id',
            '{{%construction_assignment}}','employee_id',
            '{{%employee}}','id',
            'CASCADE'
        );

        // Unique index to prevent duplicate assignments
        $this->createIndex(
            'uq-ca-site-employee',
            '{{%construction_assignment}}',
            ['construction_site_id', 'employee_id'],
            true
        );

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%construction_assignment}}');

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251222_173338_create_construction_assignment cannot be reverted.\n";

        return false;
    }
    */
}
