<?php

namespace frontend\controllers;

use common\models\ConstructionSite;
use common\models\User;
use common\models\Employee;
use common\models\EmployeeSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;
use yii;

/**
 * EmployeeController implements the CRUD actions for Employee model.
 */
class EmployeeController extends Controller
{
    
    /**
     * @inheritDoc
    */
    protected  $levels;

    public function init()
    {
        parent::init();
        $this->levels = ConstructionSite::getAccessLevels();
    }

    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['@'], // Logged-in users only
                        ],
                    ],
                ],
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Employee models.
     *
     * @return string
     */
    public function actionIndex()
    {

//         var_dump(Yii::$app->user->can('employee.view'));die;

//         dd(Yii::$app->user->id,
//         Yii::$app->authManager->getAssignments(Yii::$app->user->id),
//         Yii::$app->authManager->getRolesByUser(Yii::$app->user->id),
//         array_keys(Yii::$app->authManager->getPermissionsByUser(Yii::$app->user->id)),
// );


        if (!Yii::$app->user->can('employee.view')) {
            throw new ForbiddenHttpException();
        }

        $searchModel = new EmployeeSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]); //can use php compact('searchModel', 'dataProvider') alternative, because variable names match keys
    }

    /**
     * Displays a single Employee model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $employee = $this->findModel($id);
        $user = $employee->user;

        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        if (
            Yii::$app->user->can('employee.view') ||
            Yii::$app->user->can('employee.viewOwn', ['model' => $employee])
        ) {
            return $this->render('view', [
                'employee' => $employee,
                'user' => $user,
            ]);
        }

    throw new ForbiddenHttpException();
    }

    /**
     * Creates a new Employee model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        if (!Yii::$app->user->can('employee.create')) {
            throw new ForbiddenHttpException();
        }

        $employee = new Employee();
        $user = new User();

        if ($this->request->isPost) {

            $transaction = Yii::$app->db->beginTransaction();

            try 
            {
                if (!$user->load($this->request->post()) || !$employee->load($this->request->post())) {
                    throw new \Exception('Failed to load data');
                }

                $user->status = $user->status ?? User::STATUS_ACTIVE;
                $user->setPassword($user->password ?: Yii::$app->security->generateRandomString(10));
                $user->generateAuthKey();
                $user->generateEmailVerificationToken();
                $user->generatePasswordResetToken();

                if (!$user->save()) {
                    throw new \Exception('User not saved');
                }

                $employee->user_id = $user->id;

                if (!$employee->save()) {
                    throw new \Exception('Employee not saved');
                }

                // RBAC
                $auth = Yii::$app->authManager;
                if ($employee->role) {
                    $role = $auth->getRole($employee->role);
                    if ($role) {
                        $auth->assign($role, $user->id);
                    }
                }

                $transaction->commit();
                return $this->redirect(['view', 'id' => $employee->id]);

            } catch (\Throwable $e) {
                $transaction->rollBack();
                Yii::error($e->getMessage());
            }
        }

        return $this->render('create', [
            'employee' => $employee,
            'user' => $user,
        ]);

    }

    public function actionUpdate($id)
    {
        $employee = $this->findModel($id);
        $user = $employee->user;

        // unified permission check
        $this->assertPermissionWithLimited('employee.update', 'employee.updateLimited', ['model' => $employee]);


        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        $oldRole = $employee->role;
        $oldStatus = $user->status;

        if ($this->request->isPost) {

            $transaction = Yii::$app->db->beginTransaction();

            try {
                if (!$employee->load($this->request->post()) || !$user->load($this->request->post())) {
                    throw new \Exception('Failed to load data');
                }

                // Password change (optional)
                if ($user->password !== null && $user->password !== '') {
                    $user->setPassword($user->password);
                }

                if (!$user->save()) {
                    throw new \Exception('User not saved');
                }

                if (!$employee->save()) {
                    throw new \Exception('Employee not saved');
                }

                // RBAC role update
                $auth = Yii::$app->authManager;

                // Prevent role change unless user has full employee.update permission
                if ($oldRole !== $employee->role) {
                    if (!Yii::$app->user->can('employee.update')) {
                        throw new ForbiddenHttpException('You do not have permission to change role');
                    }

                    $auth->revokeAll($user->id);

                    if ($employee->role) {
                        $role = $auth->getRole($employee->role);
                        if ($role) {
                            $auth->assign($role, $user->id);
                        }
                    }
                }

                // Prevent status change unless user has activate/deactivate rights
                        if ($oldStatus != $user->status) {
                    if ($user->status == User::STATUS_ACTIVE) {
                        if (!Yii::$app->user->can('user.activate') && !Yii::$app->user->can('user.activateLimited', ['model' => $employee])) {
                            throw new ForbiddenHttpException('You do not have permission to activate users');
                        }
                    } else {
                        if (!Yii::$app->user->can('user.deactivate') && !Yii::$app->user->can('user.deactivateLimited', ['model' => $employee])) {
                            throw new ForbiddenHttpException('You do not have permission to deactivate users');
                        }
                    }
                }

                $transaction->commit();
                return $this->redirect(['view', 'id' => $employee->id]);

            } catch (\Throwable $e) {
                $transaction->rollBack();
                Yii::error($e->getMessage());
            }
        }

        return $this->render('update', [
            'employee' => $employee,
            'user' => $user,
        ]);
    }

    /**
     * Deletes an existing Employee model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        //TODO Employee deletion disabled for now
        throw new \yii\web\ForbiddenHttpException(
            'You dont have permission to delete Employee.');

        if (!Yii::$app->user->can('employee.delete')) {
            throw new ForbiddenHttpException();
        }
        
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Employee model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Employee the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */

    public function actionDeactivate($id)
    {

        $employee = $this->findModel($id);

        $user = $employee->user;

        // unified permission check for deactivate
        $this->assertPermissionWithLimited('user.deactivate', 'user.deactivateLimited', ['model' => $employee]);

        $user->status = User::STATUS_INACTIVE;
        $user->save(false);

        // Revoke RBAC assignments on deactivate to prevent a deactivated user from acting
        Yii::$app->authManager->revokeAll($user->id);

        return $this->redirect(['view', 'id' => $employee->id]);
    }

    public function actionActivate($id)
    {
        $employee = $this->findModel($id);

        $this->assertPermissionWithLimited('user.activate', 'user.activateLimited', ['model' => $employee]);

        $user = $employee->user;

        if ($user === null) {
            throw new NotFoundHttpException('User not found');
        }

        $user->status = User::STATUS_ACTIVE;
        $user->save(false);

        // Re-assign RBAC role from the employee record (if present)
        if ($employee->role) {
            $auth = Yii::$app->authManager;
            $role = $auth->getRole($employee->role);
            if ($role) {
                $auth->assign($role, $user->id);
            }
        }

        return $this->redirect(['view', 'id' => $employee->id]);
    }

    /**
     * Helper to assert permission with optional limited permission that requires model param.
     *
     * @param string $fullPermission
     * @param string|null $limitedPermission
     * @param array $params
     * @throws ForbiddenHttpException
     */
    protected function assertPermissionWithLimited(string $fullPermission, ?string $limitedPermission = null, array $params = [])
    {
        if ($limitedPermission !== null) {
            if (!Yii::$app->user->can($fullPermission) && !Yii::$app->user->can($limitedPermission, $params)) {
                throw new ForbiddenHttpException();
            }
        } else {
            if (!Yii::$app->user->can($fullPermission)) {
                throw new ForbiddenHttpException();
            }
        }
    }

    protected function findModel($id)
    {
        if (($model = Employee::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
