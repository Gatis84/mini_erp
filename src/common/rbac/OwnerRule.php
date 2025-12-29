<?php

namespace common\rbac;

use yii\rbac\Rule;
use common\models\Task;
use common\models\TaskAssignment;
use common\models\Employee;
use common\models\ConstructionSite;
use common\models\ConstructionAssignment;

class OwnerRule extends Rule
{
    public $name = 'isOwner';

    public function execute($userId, $item, $params)
    {

        if (\Yii::$app->user->can('sysAdmin')) {
            return true;
        }
        
        if (!isset($params['model'])) {
            return false;
        }

        $model = $params['model'];

        // TASKS
         if ($model instanceof Task) {
            return TaskAssignment::find()
                ->joinWith('employee')
                ->where([
                    'task_assignment.task_id' => $model->id,
                    'employee.user_id' => $userId,
                ])
                ->exists();
        }

        // EMPLOYEE
        if ($model instanceof Employee) {
            return $model->user_id == $userId;
        }

        // CONSTRUCTION SITE
        if ($model instanceof ConstructionSite) {
            return ConstructionAssignment::find()
                ->joinWith('employee')
                ->andWhere([
                    'construction_assignment.construction_site_id' => $model->id,
                    'employee.user_id' => $userId,
                ])
                ->exists();
        }


        return false;
    }
}
