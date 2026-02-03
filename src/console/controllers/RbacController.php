<?php

namespace console\controllers;

use common\rbac\OwnerRule;
use common\rbac\NoSameLevelEditRule;
use common\rbac\NotSelfRule;
use common\rbac\ConstructionSiteRule;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

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

        // RULE for self delete deny
        $notSelfRule = new NotSelfRule();
        $auth->add($notSelfRule);

        // RULE - OwnerRule
        $ownerRule = new OwnerRule();
        $auth->add($ownerRule);

        // RULE - NoSameLevelEditRule
        $noSameLevelEditRule = new NoSameLevelEditRule();
        $auth->add($noSameLevelEditRule);

        // RULE - ConstructionSiteRule - can create tasks only in owned construction sites
        $constructionSiteRule = new ConstructionSiteRule();
        $auth->add($constructionSiteRule);

        // User permissions

        $deleteOtherUser = $auth->createPermission('user.deleteOther');
        $deleteOtherUser->description = 'Delete other users (soft delete, cannot delete self), filtered by NotSelfRule';
        $deleteOtherUser->ruleName = $notSelfRule->name;
        $auth->add($deleteOtherUser);

        $deleteUser = $auth->createPermission('user.delete');
        $deleteUser->description = 'Delete user (soft delete)';
        $auth->add($deleteUser);

        $restoreUser = $auth->createPermission('user.restore');
        $restoreUser->description = 'Restore deleted user';
        $auth->add($restoreUser);

        $activateUser = $auth->createPermission('user.activate');
        $activateUser->description = 'Activate user';
        $auth->add($activateUser);
        
        $userActivateLimited = $auth->createPermission('user.activateLimited');
        $userActivateLimited->description = 'Activate user except same-level admins, filtered by NoSameLevelEditRule';
        $userActivateLimited->ruleName = $noSameLevelEditRule->name;
        $auth->add($userActivateLimited);
        
        $deactivateUser = $auth->createPermission('user.deactivate');
        $deactivateUser->description = 'Deactivate user';
        $auth->add($deactivateUser);

        $userDeactivateLimited = $auth->createPermission('user.deactivateLimited');
        $userDeactivateLimited->description = 'Deactivate user except same-level admins, filtered by NoSameLevelEditRule';
        $userDeactivateLimited->ruleName = $noSameLevelEditRule->name;
        $auth->add($userDeactivateLimited);

        // Employee permissions
        
        $employeeView = $auth->createPermission('employee.view');
        $employeeView->description = 'View employees (index/list access)';
        $auth->add($employeeView);
        
        $employeeViewOwn = $auth->createPermission('employee.viewOwn');
        $employeeViewOwn->description = 'View own employee profile, filtered by OwnerRule';
        $employeeViewOwn->ruleName = $ownerRule->name;
        $auth->add($employeeViewOwn);
        
        $employeeCreate = $auth->createPermission('employee.create');
        $employeeCreate->description = 'Create employee';
        $auth->add($employeeCreate);
        
        $employeeUpdate = $auth->createPermission('employee.update');
        $employeeUpdate->description = 'Update employee';
        $auth->add($employeeUpdate);
        
        $employeeUpdateLimited = $auth->createPermission('employee.updateLimited');
        $employeeUpdateLimited->description = 'Update employee except same level admins, filtered by NoSameLevelEditRule';
        $employeeUpdateLimited->ruleName = $noSameLevelEditRule->name;
        $auth->add($employeeUpdateLimited);

        // Project permissions
        $projectView = $auth->createPermission('project.view');
        $projectView->description = 'View projects (index/list access)';
        $auth->add($projectView);

        $projectViewOwn = $auth->createPermission('project.viewOwn');
        $projectViewOwn->description = 'View related projects, filtered by OwnerRule';
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
        $taskViewOwn->description = 'View own tasks, filtered by OwnerRule';
        $taskViewOwn->ruleName = $ownerRule->name;
        $auth->add($taskViewOwn);

        $taskCreate = $auth->createPermission('task.create');
        $taskCreate->description = 'Create/Save task';
        $auth->add($taskCreate);

        $taskCreateForm = $auth->createPermission('task.createForm');
        $taskCreateForm->description = 'Can create/open task form, but not save it';
        $auth->add($taskCreateForm);

        $taskCreateLimited = $auth->createPermission('task.createLimited');
        $taskCreateLimited->description = 'Create/Save task only in owned construction sites, filtered by ConstructionSiteRule';
        $taskCreateLimited->ruleName = $constructionSiteRule->name;
        $auth->add($taskCreateLimited);

        $taskUpdate = $auth->createPermission('task.update');
        $taskUpdate->description = 'Update task, filtered by ConstructionSiteRule(TeamLead can update only in owned construction sites)';
        $taskUpdate->ruleName = $constructionSiteRule->name;
        $auth->add($taskUpdate);

        $taskDelete = $auth->createPermission('task.delete');
        $taskDelete->description = 'Delete task , filtered by ConstructionSiteRule(TeamLead can delete only in owned construction sites)';
        $taskDelete->ruleName = $constructionSiteRule->name;
        $auth->add($taskDelete);

        $taskAssignmentUpdate = $auth->createPermission('taskAssignment.updateOwnStatus');
        $taskAssignmentUpdate->description = 'Own Task Assignment status update, filtered by OwnerRule';
        $taskAssignmentUpdate->ruleName = $ownerRule->name;
        $auth->add($taskAssignmentUpdate);


        /**
         * ROLES
         */

        $employee = $auth->createRole('employee');
        $employee->description = 'Employee role with basic permissions - can see his own related data';
        $auth->add($employee);

        // $auth->addChild($employee, $employeeView);
        $auth->addChild($employee, $employeeViewOwn);

        $auth->addChild($employee, $projectView);
        $auth->addChild($employee, $projectViewOwn);

        $auth->addChild($employee, $taskView);
        $auth->addChild($employee, $taskViewOwn);
        $auth->addChild($employee, $taskAssignmentUpdate);

        $teamLead = $auth->createRole('teamLead');
        $teamLead->description = 'Team Lead role with extended permissions';
        $auth->add($teamLead);

        $auth->addChild($teamLead, $employee);
        $auth->addChild($teamLead, $employeeView);

        // $auth->addChild($teamLead, $taskCreate);
        $auth->addChild($teamLead, $taskCreateLimited);
        $auth->addChild($teamLead, $taskCreateForm);
        $auth->addChild($teamLead, $taskUpdate);
        $auth->addChild($teamLead, $taskDelete);
        
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
        $auth->addChild($admin, $taskUpdate);
        $auth->addChild($admin, $taskCreate);

        $sysAdmin = $auth->createRole('sysAdmin');
        $sysAdmin->description = 'System Administrator with full permissions including user management';
        $auth->add($sysAdmin);

        $auth->addChild($sysAdmin, $admin);
        $auth->addChild($sysAdmin, $deleteUser);
        $auth->addChild($sysAdmin,$deleteOtherUser);
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
            return ExitCode::UNAVAILABLE;
        }
        $auth->assign($rbacRole, (string)$userId);
    }
}
