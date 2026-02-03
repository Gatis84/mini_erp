<?php

namespace backend\modules\user\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use common\models\User;
use common\models\UserSearch;
use common\models\Employee;
use common\services\RoleChangeService;
use common\services\AssignmentService;

class UserController extends Controller
{
    public function actionIndex()
    {
        $message = "Access denied!\n";

        if (!Yii::$app->user->can('user.restore')) {
            throw new ForbiddenHttpException($message);
        }

        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        if (!Yii::$app->user->can('user.restore')) {
            throw new ForbiddenHttpException();
        }

        $user = $this->findUser($id);

        return $this->render('view', [
            'user' => $user,
        ]);
    }

    public function actionDelete($id)
    {
        $user = $this->findUser($id);
        if (!Yii::$app->user->can('user.deleteOther', ['targetUserId' => $user->id])) {
            throw new ForbiddenHttpException();
        }
        
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $user->status = User::STATUS_DELETED;
            $user->save(false);

            // delete employee profile (1:1)
            $employee = Employee::findOne(['user_id' => $user->id]);
            if ($employee) {
                $employee->delete();
            }

            // capture and cleanup domain data for permissions being revoked
            $auth = Yii::$app->authManager;
            $oldPermissions = array_keys($auth->getPermissionsByUser($user->id));

            Yii::$app->authManager->revokeAll($user->id);

            RoleChangeService::syncAfterRoleChange($user->id, $oldPermissions, []);

            // Ensure assignments are deactivated even if no permission mapping matched
            AssignmentService::deactivateAssignmentsForUser($user->id);

            $transaction->commit();
            return $this->redirect(['index']);
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage());
            throw $e;
        }
    }

    public function actionRestore($id)
    {
        if (!Yii::$app->user->can('user.restore')) {
            throw new ForbiddenHttpException();
        }

        $user = User::findOne(['id' => $id, 'status' => User::STATUS_DELETED]);
        if (!$user) {
            throw new NotFoundHttpException('Deleted user not found');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $user->status = User::STATUS_INACTIVE;
            $user->save(false);

            // recreate employee profile if missing
            $employee = Employee::findOne(['user_id' => $user->id]);
            if (!$employee) {
                $employee = new Employee();
                $employee->user_id = $user->id;
                $employee->first_name = $user->username;
                $employee->last_name = '';
                $employee->access_level = 1;
                $employee->role = 'employee';
                $employee->save(false);
            }

            $transaction->commit();
            return $this->redirect(['view', 'id' => $user->id]);
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage());
            throw $e;
        }
    }

    public function actionDeactivate($id)
    {
        $user = User::findOne($id);
        if (!$user) {
            throw new NotFoundHttpException();
        }

        // If an Employee record exists, allow limited checks that take model into account
        $employee = Employee::findOne(['user_id' => $user->id]);
        if ($employee) {
            if (!Yii::$app->user->can('user.deactivate') && !Yii::$app->user->can('user.deactivateLimited', ['model' => $employee])) {
                throw new ForbiddenHttpException();
            }
        } else {
            if (!Yii::$app->user->can('user.deactivate')) {
                throw new ForbiddenHttpException();
            }
        }

        // Capture current permissions before revoking so we can cleanup domain data
        $auth = Yii::$app->authManager;
        $oldPermissions = array_keys($auth->getPermissionsByUser($user->id));

        $user->status = User::STATUS_INACTIVE;
        $user->save(false);

        // Revoke RBAC assignments on deactivate to prevent a deactivated user from acting
        $auth->revokeAll($user->id);

        // Cleanup domain data for revoked permissions (e.g., deactivate assignments)
        RoleChangeService::syncAfterRoleChange($user->id, $oldPermissions, []);

        // Ensure assignments are deactivated even if no permission mapping matched
        AssignmentService::deactivateAssignmentsForUser($user->id);

        return $this->redirect(Yii::$app->request->referrer ?? ['index']);
    }

    public function actionActivate($id)
    {
        $user = User::findOne($id);
        if (!$user) {
            throw new NotFoundHttpException();
        }

        // If an Employee record exists, allow limited checks that take model into account
        $employee = Employee::findOne(['user_id' => $user->id]);
        if ($employee) {
            if (!Yii::$app->user->can('user.activate') && !Yii::$app->user->can('user.activateLimited', ['model' => $employee])) {
                throw new ForbiddenHttpException();
            }
        } else {
            if (!Yii::$app->user->can('user.activate')) {
                throw new ForbiddenHttpException();
            }
        }

        $user->status = User::STATUS_ACTIVE;
        $user->save(false);

        // Re-assign RBAC role from the employee record (if present)
        if ($employee && $employee->role) {
            $auth = Yii::$app->authManager;
            $role = $auth->getRole($employee->role);
            if ($role) {
                $auth->assign($role, $user->id);
            }
        }

        // Reactivate assignments for the employee(s) of this user
        AssignmentService::reactivateAssignmentsForUser($user->id);

        return $this->redirect(Yii::$app->request->referrer ?? ['index']);
    }


    protected function findUser($id): User
    {
        $user = User::findOne($id);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }
        return $user;
    }
}
