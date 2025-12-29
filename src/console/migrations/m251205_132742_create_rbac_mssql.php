<?php

use yii\db\Migration;

class m251205_132742_create_rbac_mssql extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
{

    // The data columns in auth_item and auth_rule use 'VARBINARY(MAX)', 
    // but Yii2's rbac/init inserts serialized PHP strings (varchar data),
    // causing the exact "varchar to varbinary" conversion error.
    // Yii2 official MSSQL RBAC schema also uses varbinary but requires triggers/workarounds.
    // Example: MSSQL QueryBuilder doesn`t have ->primaryKey()

    // auth_rule
    $this->createTable('{{%auth_rule}}', [
        'name' => $this->string(64)->notNull(),
        'data' => $this->text()->null(),  // varchar(max)
        'created_at' => $this->integer(),
        'updated_at' => $this->integer(),
    ]);
    $this->addPrimaryKey('pk_auth_rule', '{{%auth_rule}}', 'name');

    // auth_item
    $this->createTable('{{%auth_item}}', [
        'name' => $this->string(64)->notNull(),
        'type' => $this->smallInteger()->notNull(),
        'description' => $this->text(),
        'rule_name' => $this->string(64),
        'data' => $this->text()->null(),  // varchar(max)
        'created_at' => $this->integer(),
        'updated_at' => $this->integer(),
    ]);
    $this->addPrimaryKey('pk_auth_item', '{{%auth_item}}', 'name');

    $this->addForeignKey('fk_auth_item_rule', '{{%auth_item}}', 'rule_name', '{{%auth_rule}}', 'name');

    // auth_item_child
    $this->createTable('{{%auth_item_child}}', [
        'parent' => $this->string(64)->notNull(),
        'child' => $this->string(64)->notNull(),
    ]);
    $this->addPrimaryKey('pk_auth_item_child', '{{%auth_item_child}}', ['parent', 'child']);

    $this->addForeignKey('fk_auth_item_child_parent', '{{%auth_item_child}}', 'parent', '{{%auth_item}}', 'name');
    $this->addForeignKey('fk_auth_item_child_child', '{{%auth_item_child}}', 'child', '{{%auth_item}}', 'name');

    // auth_assignment
    $this->createTable('{{%auth_assignment}}', [
        'item_name' => $this->string(64)->notNull(),
        'user_id' => $this->string(64)->notNull(),
        'created_at' => $this->integer(),
    ]);
    $this->addForeignKey('fk_auth_assignment_item', '{{%auth_assignment}}', 'item_name', '{{%auth_item}}', 'name');
}

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%auth_assignment}}');
        $this->dropTable('{{%auth_item_child}}');
        $this->dropTable('{{%auth_item}}');
        $this->dropTable('{{%auth_rule}}');  
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251205_132742_create_rbac_mssql cannot be reverted.\n";

        return false;
    }
    */
}
