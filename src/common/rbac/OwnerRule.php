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
        
        if (!isset($params['model'])) {
            return false;
        }
            
        $model = $params['model'];

        // TaskAssignment — employee owns his assignment
        if ($model instanceof TaskAssignment) {
                $employeeId = Employee::find()
                    ->select('id')
                    ->where(['user_id' => $userId])
                    ->scalar();

            return $employeeId && $model->employee_id == $employeeId;
        }

        // TASKS — employee assigned to task
        if ($model instanceof Task) {
            return TaskAssignment::find()
                ->joinWith('employee')
                ->where([
                    'task_assignment.task_id' => $model->id,
                    'employee.user_id' => $userId,
                ])
                ->exists();
        }

        // EMPLOYEE — own profile
        if ($model instanceof Employee) {
            return $model->user_id == $userId;
        }

        return false;
    }
}
