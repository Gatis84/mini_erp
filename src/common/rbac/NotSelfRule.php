<?php

namespace common\rbac;

use yii\rbac\Rule;

class NotSelfRule extends Rule
{
    public $name = 'isNotSelf';

    public function execute($userId, $item, $params)
    {

        if (!isset($params['targetUserId'])) {
            return false;
        }

        return (int)$params['targetUserId'] !== (int)$userId;
    }
}
