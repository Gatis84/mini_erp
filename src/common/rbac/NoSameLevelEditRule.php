<?php 

namespace common\rbac;

use yii\rbac\Rule;
use Yii;
use common\models\Employee;

class NoSameLevelEditRule extends Rule
{
    public $name = 'noSameLevelEdit';

    public function execute($userId, $item, $params)
    {
        if (Yii::$app->user->can('sysAdmin')) {
            return true;
        }

        if (!isset($params['model']) || !$params['model'] instanceof Employee) {
            return false;
        }

        $targetEmployee = $params['model'];

        $auth = Yii::$app->authManager;
        $targetRoles = $auth->getRolesByUser($targetEmployee->user_id);

        if (isset($targetRoles['admin'])) {
            return false;
        }

        return true;
    }
}
