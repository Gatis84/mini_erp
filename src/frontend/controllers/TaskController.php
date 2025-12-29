<?php

namespace frontend\controllers;

use Yii;
use common\models\Employee;
use common\models\Task;
use common\models\TaskAssignment;
use common\models\TaskSearch;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

class TaskController extends Controller
{
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


    // TASK LIST
    public function actionIndex()
    {
        if (!Yii::$app->user->can('task.view')) {
            throw new ForbiddenHttpException();
        }

        $searchModel = new TaskSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    // VIEW TASK
    public function actionView($id)
    {
        $model = $this->findModel($id);

        if (
            Yii::$app->user->can('task.view') ||
            Yii::$app->user->can('task.viewOwn', ['model' => $model])
        ) {
            return $this->render('view', ['model' => $model]);
        }

        throw new ForbiddenHttpException();
    }

    // CREATE TASK

    public function actionCreate()
    {

        $isFull = Yii::$app->user->can('task.create');
        $isLimited = Yii::$app->user->can('task.createLimited');

        if (!$isFull && !$isLimited) {
            throw new ForbiddenHttpException();
        }

        $model = new Task();
        $assignment = new TaskAssignment();

        if ($this->request->isPost) {

            if ($model->load($this->request->post()) && $assignment->load($this->request->post())) {

                // TEAM LEAD — can only create tasks in own projects
                if ($isLimited && !$isFull) {

                    $employee = Employee::findOne(['user_id' => Yii::$app->user->id]);
                    if (!$employee) {
                        throw new ForbiddenHttpException();
                    }

                    $allowedSites = (new \yii\db\Query())
                        ->select('construction_site_id')
                        ->from('construction_assignment')
                        ->where(['employee_id' => $employee->id])
                        ->column();

                    if (!in_array((int)$model->construction_site_id, array_map('intval', $allowedSites), true)) {
                        throw new ForbiddenHttpException('You are not allowed to create tasks in this project');
                    }
                }

                $transaction = Yii::$app->db->beginTransaction();

                try {
                    if (!$model->save()) {
                        throw new \Exception('Task save failed');
                    }

                    $employeeIds = $assignment->employee_ids ?? [];
                    foreach ($employeeIds as $employeeId) {
                        $ta = new TaskAssignment();
                        $ta->task_id = $model->id;
                        $ta->employee_id = (int)$employeeId;
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
                        'Frontend task create failed',
                        'exception' => $e,
                        'taskErrors' => $model->getErrors(),
                        'assignmentErrors' => $assignment->getErrors(),
                    ]);

                    Yii::$app->session->setFlash('error', 'Task could not be created.');
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
            'assignment' => $assignment,
        ]);
    }


    // UPDATE TASK

    public function actionUpdate($id)
    {
        if (!Yii::$app->user->can('task.update')) {
            throw new ForbiddenHttpException();
        }

        $model = $this->findModel($id);
        $assignment = new TaskAssignment();

        $existingAssignments = TaskAssignment::find()
            ->where(['task_id' => $model->id])
            ->all();

        $assignment->employee_ids = array_map(
            fn($a) => $a->employee_id,
            $existingAssignments
        );

        if ($this->request->isPost) {

            if ($model->load($this->request->post()) && $assignment->load($this->request->post())) {

                /**
                 * TEAM LEAD — can only update tasks in own projects
                 * ADMIN — skips this check
                 */
                if (
                    Yii::$app->user->can('task.updateLimited')
                    && !Yii::$app->user->can('task.update')
                ) {
                    $employee = Employee::findOne(['user_id' => Yii::$app->user->id]);
                    if (!$employee) {
                        throw new ForbiddenHttpException();
                    }

                    $allowedSites = (new \yii\db\Query())
                        ->select('construction_site_id')
                        ->from('construction_assignment')
                        ->where(['employee_id' => $employee->id])
                        ->column();

                    if (!in_array($model->construction_site_id, $allowedSites)) {
                        throw new ForbiddenHttpException(
                            'You are not allowed to update tasks in this project'
                        );
                    }
                }

                $transaction = Yii::$app->db->beginTransaction();

                try {
                    if (!$model->save()) {
                        throw new \Exception('Task update failed');
                    }

                    $newEmployeeIds = $assignment->employee_ids ?? [];

                    TaskAssignment::deleteAll([
                        'AND',
                        ['task_id' => $model->id],
                        ['NOT IN', 'employee_id', $newEmployeeIds],
                    ]);

                    $existingMap = array_flip($assignment->employee_ids ?? []);

                    foreach ($newEmployeeIds as $employeeId) {
                        $exists = TaskAssignment::find()
                            ->where([
                                'task_id' => $model->id,
                                'employee_id' => $employeeId,
                            ])
                            ->exists();

                        if (!$exists) {
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
                        'Frontend task update failed',
                        'exception' => $e,
                        'taskErrors' => $model->getErrors(),
                    ]);

                    Yii::$app->session->setFlash(
                        'error',
                        'Task could not be updated.'
                    );
                }
            }
        }

        return $this->render('update', [
            'model' => $model,
            'assignment' => $assignment,
        ]);
    }

    // DELETE TASK
    public function actionDelete($id)
    {
        
        if (!Yii::$app->user->can('task.delete')) {
            throw new ForbiddenHttpException();
        }
        
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = Task::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException();
    }
}
