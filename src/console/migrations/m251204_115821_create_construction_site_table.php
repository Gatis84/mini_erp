<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%construction_site}}`.
 */
class m251204_115821_create_construction_site_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%construction_site}}', [
            'id' => $this->primaryKey(),
            'location' => $this->string()->notNull(),
            'area_m2' => $this->integer()->notNull(),
            'required_access_level' => $this->integer()->notNull(),
            'created_at' => $this->dateTime()->defaultExpression('GETDATE()'),
            'updated_at' =>  $this->dateTime()->defaultExpression('GETDATE()'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%construction_site}}');
    }
}
