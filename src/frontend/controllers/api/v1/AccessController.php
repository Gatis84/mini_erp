<?php

namespace frontend\controllers\api\v1;

use yii\web\Controller;
use yii\web\Response;
use yii\db\Query;
use Yii;


class AccessController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [];
    }

    /*
    An employee has access to a construction site on date D if:
    - he has a task_assignment
    - the task belongs to construction_site
    - D is between planned_start_at and planned_end_at
    // - the status is not canceled
    - the employee's access_level ≥ required_access_level
    - or the employee is an admin / manager
    */

    /**
     * API:
     * validates if employee has access to construction site on given date
     * 
     * Summary of actionValidate
     * @param mixed $employee_id
     * @param mixed $site_id
     * @param mixed $date
     */
    public function actionValidate($employee_id, $site_id, $date)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Employee
        $employee = (new Query())
            ->from('employee')
            ->where(['id' => $employee_id])
            ->one();

        if (!$employee) {
            return $this->deny('Employee not found');
        }

        $userId = $employee['user_id'];
        $auth = Yii::$app->authManager;

        // Check access level
        $accessLevelCheck = $this->CheckAccessLevel($employee_id, $site_id);
        // if (!$accessLevelCheck['allowed']) {
        //     return $accessLevelCheck;
        // }

        // SysAdmin & Admin always allowed
        if ($auth->checkAccess($userId, 'project.create')) {

            if($this->hasTaskOnDate($employee_id, $site_id, $date)) {
                return $this->allow("RBAC: admin/sysAdmin has task on date between planned_start_at and planned_end_at. {$accessLevelCheck['reason']}");
            }

            return $this->allow("RBAC: admin/sysAdmin full access, but no task found on date between planned_start_at and planned_end_at. {$accessLevelCheck['reason']}");
        }

        /**
         * TeamLead/employee
         *  - can access if he is assigned to the construction site
         *  - OR has a task on the site
         */
        if ($auth->checkAccess($userId, 'project.view') 
            || $auth->checkAccess($userId, 'task.viewOwn') ) {

            if ($this->isTeamLeadOfProject($employee_id, $site_id)) {

                if ($this->hasTaskOnDate($employee_id, $site_id, $date)) {
                    return $this->allow("RBAC: teamLead is assigned to project and has task on date between planned_start_at and planned_end_at. {$accessLevelCheck['reason']}");
                }

                return $this->deny("RBAC: teamLead is assigned to project but has no task on date between planned_start_at and planned_end_at. {$accessLevelCheck['reason']}");

            } elseif ($this->hasTaskOnDate($employee_id, $site_id, $date)) {

                return $this->allow("RBAC: employee has task on date between planned_start_at and planned_end_at. {$accessLevelCheck['reason']}");
            }

            return $this->deny("RBAC: employee is not assigned to project and has no task on date between planned_start_at and planned_end_at. {$accessLevelCheck['reason']}");

        }

        return $this->deny('No matching RBAC permissions OR No task assigned for given date');
    }

    private function allow(string $reason)
    {
        return [
            'allowed' => true,
            'reason' => $reason,
        ];
    }

    private function deny(string $reason)
    {
        return [
            'allowed' => false,
            'reason' => $reason,
        ];
    }

    private function isTeamLeadOfProject(int $employeeId, int $siteId): bool
    {
        return (new Query())
            ->from('construction_assignment')
            ->where([
                'employee_id' => $employeeId,
                'construction_site_id' => $siteId,
            ])
            ->exists();
    }

    private function hasTaskOnDate(int $employeeId, int $siteId, string $date): bool
    {
        return (new Query())
            ->from('task_assignment ta')
            ->innerJoin('task t', 't.id = ta.task_id')
            ->where([
                'ta.employee_id' => $employeeId,
                't.construction_site_id' => $siteId,
            ])
            ->andWhere(['<=', 't.planned_start_at', $date])
            ->andWhere(['>=', 't.planned_end_at', $date])
            // ->andWhere(['in', 't.status', ['active', 'draft']])
            ->exists();
    }

    private function CheckAccessLevel(int $employeeId, int $siteId): array|bool
    {
        $employeeLevel = (int)(new Query())
            ->from('employee')
            ->select('access_level')
            ->where(['id' => $employeeId])
            ->scalar();

        $siteLevel = (int)(new Query())
            ->from('construction_site')
            ->select('required_access_level')
            ->where(['id' => $siteId])
            ->scalar();

        if ($employeeLevel < $siteLevel) {
            return [
                'allowed' => false,
                'reason' => "Employee access level too low (Expected site level: {$siteLevel}, Currently owned access level: {$employeeLevel})",
            ];
        }

        return [
            'allowed' => true,
            'reason' => "Employee access level sufficient (Current Employee access level: {$employeeLevel}, Expected site level: {$siteLevel})",
        ];
    }

}
