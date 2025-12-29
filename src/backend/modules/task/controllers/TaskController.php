<?php

namespace backend\modules\task\controllers;

use Yii;
use common\models\Task;
use common\models\TaskAssignment;
use common\models\TaskSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * TaskController implements the CRUD actions for Task model.
 */
class TaskController extends Controller
{
    /**
     * @inheritDoc
     */
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
     * Lists all Task models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new TaskSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Task model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Task model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Task();
        $assignment = new TaskAssignment();

        if ($this->request->isPost) {

            if ($model->load($this->request->post()) && $assignment->load($this->request->post())) {

                $transaction = Yii::$app->db->beginTransaction();

                try {
                    if (!$model->save()) {
                        throw new \Exception('Task save failed');
                    }

                   foreach ($assignment->employee_ids as $employeeId) {
                        $ta = new TaskAssignment();
                        $ta->task_id = $model->id;
                        $ta->employee_id = $employeeId;
                        $ta->status = TaskAssignment::STATUS_ASSIGNED;
                        $ta->assigned_at = date('Y-m-d H:i:s');

                        if (!$ta->save()) {
                            throw new \Exception('TaskAssignment save failed');
                        }
                    }

                    $transaction->commit();

                    return $this->redirect(['view', 'id' => $model->id]);

                } catch (\Throwable $e) {

                    $transaction->rollBack();

                    Yii::error([
                        'Task create failed',
                        'exception' => $e,
                        'taskErrors' => $model->getErrors(),
                        'assignmentErrors' => $assignment->getErrors(),
                    ]);

                    Yii::$app->session->setFlash(
                        'error',
                        'Task could not be saved. Please try again.'
                    );
                }
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
            'assignment' => $assignment,
        ]);
    }

    /**
     * Updates an existing Task model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $assignment = new TaskAssignment();

        $existingAssignments = TaskAssignment::find()
            ->where(['task_id' => $model->id])
            ->all();

        $existingEmployeeIds = array_map(
             fn($a) => $a->employee_id,
            $existingAssignments
        );

        $assignment->employee_ids = $existingEmployeeIds;
        
        if ($this->request->isPost) {

            if ($model->load($this->request->post()) && $assignment->load($this->request->post())) {

                $transaction = Yii::$app->db->beginTransaction();

                try {
                    if (!$model->save()) {
                        throw new \Exception('Task update failed');
                    }

                    $newEmployeeIds = $assignment->employee_ids ?? [];

                    $existingMap = array_flip($existingEmployeeIds);

                    TaskAssignment::deleteAll([
                        'AND',
                        ['task_id' => $model->id],
                        ['NOT IN', 'employee_id', $newEmployeeIds],
                    ]);

                    foreach ($newEmployeeIds as $employeeId) {
                        if (!isset($existingMap[$employeeId])) {
                            $ta = new TaskAssignment();
                            $ta->task_id = $model->id;
                            $ta->employee_id = $employeeId;
                            $ta->status = TaskAssignment::STATUS_ASSIGNED;
                            $ta->assigned_at = date('Y-m-d H:i:s');

                            if (!$ta->save()) {
                                throw new \Exception('TaskAssignment save failed');
                            }
                        }
                    }

                    $transaction->commit();

                    return $this->redirect(['view', 'id' => $model->id]);

                } catch (\Throwable $e) {

                    $transaction->rollBack();

                    Yii::error([
                        'Task update failed',
                        'exception' => $e,
                        'taskErrors' => $model->getErrors(),
                        'assignmentErrors' => $assignment->getErrors(),
                    ]);

                    Yii::$app->session->setFlash(
                        'error',
                        'Task could not be updated. Please try again.'
                    );
                }
            }
        }

        return $this->render('update', [
            'model' => $model,
            'assignment' => $assignment,
        ]);
    }

    /**
     * Deletes an existing Task model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Task model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Task the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Task::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
