<?php

namespace backend\modules\employee\controllers;

use common\models\ConstructionSite;
use Yii;
use common\models\Employee;
use common\models\EmployeeSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\User;
use yii\web\ForbiddenHttpException;

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
                            'actions' => ['index', 'view', 'create', 'update', 'delete'],
                            'roles' => ['sysAdmin'],
                        ],
                    ],
                    'denyCallback' => function ($rule, $action) {
                        $auth = Yii::$app->authManager;
                        $userId = Yii::$app->user->id;
                        
                        // Current user roles
                        $userRoles = array_keys($auth->getRolesByUser($userId));
                        
                        // Get required role from current rule
                        $requiredRole = 'unknown';
                        if ($rule && $rule->roles && !empty($rule->roles)) {
                            $requiredRole = implode(', ', $rule->roles);
                        }

                        $message = "Access denied!\n";
                        $message .= "Action: " . $action->id . "\n";
                        $message .= "Required role: {$requiredRole}\n";
                        $message .= "Your roles: " . (empty($userRoles) ? 'none' : implode(', ', $userRoles));
                        
                        throw new \yii\web\ForbiddenHttpException($message);
                    }
                    /*
                    'denyCallback' => function ($rule, $action) {
                        $auth = Yii::$app->authManager;
                        $userId = Yii::$app->user->id;
                        
                        // Get all roles for current user
                        $roles = $auth->getRolesByUser($userId);
                        $userRoles = array_keys($roles);
                        
                        $message = "Access denied! Required permissions not met.\n";
                        $message .= "Your roles: " . implode(', ', $userRoles) . "\n";
                        $message .= "Contact admin for access.";
                        
                        throw new \yii\web\ForbiddenHttpException($message);
}
                    */
                ],
                /*
                VerbFilter ensures the delete action only accepts POST requests,
                preventing accidental deletions via direct GET links 
                (like ?r=employee/delete&id=33) and returning HTTP 405 
                "Method Not Allowed" instead
                */
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
        $searchModel = new EmployeeSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
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

        return $this->render('view', [
            'employee' => $employee,
            'user' => $user,
        ]);
    }

    /**
     * Creates a new Employee model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $employee = new Employee();
        $user = new User();

        if (!Yii::$app->user->can('sysAdmin')) {
            throw new ForbiddenHttpException();
        }

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
                // $employee->status = $user->status;

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

    /**
     * Updates an existing Employee model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $employee = $this->findModel($id);
        $user = $employee->user;

        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        $oldRole = $employee->role;

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

                if ($oldRole !== $employee->role) {
                    $auth->revokeAll($user->id);

                    if ($employee->role) {
                        $role = $auth->getRole($employee->role);
                        if ($role) {
                            $auth->assign($role, $user->id);
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
        throw new \yii\web\ForbiddenHttpException('Employee deletion is not allowed. Delete User instead.');

        if (!Yii::$app->user->can('user.delete')) {
            throw new ForbiddenHttpException();
        }

        $user = User::findOne($id);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Soft-delete user
            $user->status = User::STATUS_DELETED;
            if (!$user->save(false)) {
                throw new \Exception('User not deleted');
            }

            // Delete employee (1:1 relationship)
            if ($user->employee) {
                $user->employee->delete(); // physically OK
            }

            // Remove RBAC
            Yii::$app->authManager->revokeAll($user->id);

            $transaction->commit();

            return $this->redirect(['index']);

        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage());
            throw $e;
        }
    }

    //TODO - audit log (who and when deleted/restored user)
    // Restore a deleted Employee and associated User - can do just sysAdmin
    public function actionRestore($id)
    {
        if (!Yii::$app->user->can('user.restore')) {
            throw new ForbiddenHttpException();
        }

        $user = User::find()
            ->where(['id' => $id, 'status' => User::STATUS_DELETED])
            ->one();

        if (!$user) {
            throw new NotFoundHttpException('Deleted user not found');
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // restore user
            $user->status = User::STATUS_INACTIVE;
            if (!$user->save(false)) {
                throw new \Exception('User not restored');
            }

            // restore employee profile if missing
            if (!$user->employee) {
                $employee = new Employee();
                $employee->user_id = $user->id;
                $employee->first_name = $user->username;
                $employee->save(false);
            }

            //SysAdmin after restore Manually:
            // check data
            // assign role
            // activate user

            $transaction->commit();

            return $this->redirect(['view', 'id' => $user->id]);

        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage());
            throw $e;
        }
    }


    /**
     * Finds the Employee model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Employee the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Employee::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
