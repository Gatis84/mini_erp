<?php

namespace console\controllers;

use common\rbac\OwnerRule;
use common\rbac\NoSameLevelEditRule;
use Yii;
use yii\console\Controller;

/**
 * Role hierarchy:
 *
 * sysAdmin
 *   └── admin
 *        └── teamLead
 *             └── employee
 */
class RbacController extends Controller
{
    public function actionInit()
    {
        $auth = Yii::$app->authManager;

        $auth->removeAll();

        // RULE - OwnerRule
        $ownerRule = new OwnerRule();
        $auth->add($ownerRule);

        // RULE - NoSameLevelEditRule
        $noSameLevelEditRule = new NoSameLevelEditRule();
        $auth->add($noSameLevelEditRule);

        /**
         * PERMISSIONS
         */

        // User permissions
        $deleteUser = $auth->createPermission('user.delete');
        $deleteUser->description = 'Delete user (soft delete)';
        $auth->add($deleteUser);

        $restoreUser = $auth->createPermission('user.restore');
        $restoreUser->description = 'Restore deleted user';
        $auth->add($restoreUser);

        $activateUser = $auth->createPermission('user.activate');
        $activateUser->description = 'Activate user';
        $auth->add($activateUser);

        $deactivateUser = $auth->createPermission('user.deactivate');
        $deactivateUser->description = 'Deactivate user';
        $auth->add($deactivateUser);

        // Employee permissions
        $employeeUpdateLimited = $auth->createPermission('employee.updateLimited');
        $employeeUpdateLimited->description = 'Update employee except same level admins';
        $employeeUpdateLimited->ruleName = $noSameLevelEditRule->name;
        $auth->add($employeeUpdateLimited);

        $userDeactivateLimited = $auth->createPermission('user.deactivateLimited');
        $userDeactivateLimited->description = 'Deactivate user except same-level admins';
        $userDeactivateLimited->ruleName = $noSameLevelEditRule->name;
        $auth->add($userDeactivateLimited);

        $userActivateLimited = $auth->createPermission('user.activateLimited');
        $userActivateLimited->description = 'Activate user except same-level admins';
        $userActivateLimited->ruleName = $noSameLevelEditRule->name;
        $auth->add($userActivateLimited);


        $employeeView = $auth->createPermission('employee.view');
        $employeeView->description = 'View employees (index/list access)';
        $auth->add($employeeView);

        $employeeViewOwn = $auth->createPermission('employee.viewOwn');
        $employeeViewOwn->description = 'View own employee profile';
        $employeeViewOwn->ruleName = $ownerRule->name;
        $auth->add($employeeViewOwn);

        $employeeCreate = $auth->createPermission('employee.create');
        $employeeCreate->description = 'Create employee';
        $auth->add($employeeCreate);

        $employeeUpdate = $auth->createPermission('employee.update');
        $employeeUpdate->description = 'Update employee';
        $auth->add($employeeUpdate);

        // Project permissions
        $projectView = $auth->createPermission('project.view');
        $projectView->description = 'View projects (index/list access)';
        $auth->add($projectView);

        $projectViewOwn = $auth->createPermission('project.viewOwn');
        $projectViewOwn->description = 'View related projects';
        $projectViewOwn->ruleName = $ownerRule->name;
        $auth->add($projectViewOwn);

        $projectCreate = $auth->createPermission('project.create');
        $projectCreate->description = 'Create project';
        $auth->add($projectCreate);

        $projectUpdate = $auth->createPermission('project.update');
        $projectUpdate->description = 'Update project';
        $auth->add($projectUpdate);

        $projectDelete = $auth->createPermission('project.delete');
        $projectDelete->description = 'Delete project';
        $auth->add($projectDelete);

        // Task permissions
        $taskView = $auth->createPermission('task.view');
        $taskView->description = 'View tasks (index/list access)';
        $auth->add($taskView);

        $taskViewOwn = $auth->createPermission('task.viewOwn');
        $taskViewOwn->description = 'View own tasks';
        $taskViewOwn->ruleName = $ownerRule->name;
        $auth->add($taskViewOwn);

        $taskCreate = $auth->createPermission('task.create');
        $taskCreate->description = 'Create task';
        $auth->add($taskCreate);

        $taskCreateLimited = $auth->createPermission('task.createLimited');
        $taskCreateLimited->description = 'Create task with access level limit';
        $auth->add($taskCreateLimited);

        $taskUpdate = $auth->createPermission('task.update');
        $taskUpdate->description = 'Update task';
        $auth->add($taskUpdate);

        $taskDelete = $auth->createPermission('task.delete');
        $taskDelete->description = 'Delete task';
        $auth->add($taskDelete);

        /**
         * ROLES
         */

        $employee = $auth->createRole('employee');
        $employee->description = 'Employee role with basic permissions - can see his own related data';
        $auth->add($employee);

        $auth->addChild($employee, $employeeView);
        $auth->addChild($employee, $employeeViewOwn);

        $auth->addChild($employee, $projectView);
        $auth->addChild($employee, $projectViewOwn);

        $auth->addChild($employee, $taskView);
        $auth->addChild($employee, $taskViewOwn);

        $teamLead = $auth->createRole('teamLead');
        $teamLead->description = 'Team Lead role with extended permissions';
        $auth->add($teamLead);

        $auth->addChild($teamLead, $employee);

        // TeamLead: can manage tasks/projects more widely
        // $auth->addChild($teamLead, $taskCreate);
        $auth->addChild($teamLead, $taskCreateLimited);
        // $auth->addChild($teamLead, $taskUpdate);
        // $auth->addChild($teamLead, $projectUpdate);

        $admin = $auth->createRole('admin');
        $admin->description = 'Admin role with almost full permissions - cannot delete users';
        $auth->add($admin);

        $auth->addChild($admin, $teamLead);

        $auth->addChild($admin, $employeeCreate);
        // $auth->addChild($admin, $employeeUpdate);
        // $auth->addChild($admin, $activateUser);
        // $auth->addChild($admin, $deactivateUser);
        $auth->addChild($admin, $employeeUpdateLimited);
        $auth->addChild($admin, $userDeactivateLimited);
        $auth->addChild($admin, $userActivateLimited);

        $auth->addChild($admin, $projectCreate);
        $auth->addChild($admin, $projectUpdate);
        $auth->addChild($admin, $projectDelete);
        $auth->addChild($admin, $taskDelete);

        $sysAdmin = $auth->createRole('sysAdmin');
        $sysAdmin->description = 'System Administrator with full permissions including user management';
        $auth->add($sysAdmin);

        $auth->addChild($sysAdmin, $admin);
        $auth->addChild($sysAdmin, $deleteUser);
        $auth->addChild($sysAdmin, $restoreUser);
        $auth->addChild($sysAdmin, $employeeUpdate);
        $auth->addChild($sysAdmin, $deactivateUser);
        $auth->addChild($sysAdmin, $activateUser);
        echo "RBAC successfully initialized.\n";
    }

    public function actionAssign($role, $userId)
    {
        $auth = Yii::$app->authManager;
        $rbacRole = $auth->getRole($role);
        if ($rbacRole === null) {
            $this->stderr("Role not found: {$role}\n");
            return Controller::EXIT_CODE_ERROR;
        }
        $auth->assign($rbacRole, (string)$userId);
    }
}
