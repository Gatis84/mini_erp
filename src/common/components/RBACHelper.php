<?php

namespace common\components;

use yii\db\Query;

class RbacHelper
{
    /**
     * Returns an array of user IDs who have the specified permission.
     */
    public static function userIdsByPermission(string $permission): array
    {
        return (new Query())
            ->select('aa.user_id')
            ->from('{{%auth_assignment}} aa')
            ->innerJoin(
                '{{%auth_item_child}} aic',
                'aic.parent = aa.item_name'
            )
            ->where(['aic.child' => $permission])
            ->column();
    }
}
