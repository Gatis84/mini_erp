<?php 
namespace frontend\controllers;
use Yii;
use common\models\TaskAssignment;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class TaskAssignmentController extends Controller
{

    public function actionUpdateStatusAjax()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');
        $status = (int) Yii::$app->request->post('status');

        $assignment = TaskAssignment::findOne($id);

        if (!$assignment) {
            throw new NotFoundHttpException();
        }

        if (!Yii::$app->user->can('taskAssignment.updateOwnStatus', [
            'model' => $assignment,
        ])) {
            throw new ForbiddenHttpException();
        }

        $assignment->status = $status;

        $task = $assignment->task;

        if ($status === TaskAssignment::STATUS_COMPLETED) {
            if ($task) {
                $task->completed_at = date('Y-m-d H:i:s');
                $task->save(false);
            }
        } else {
            // If there are other completed assignments for this task, keep that task.completed_at
            if ($task) {
                $otherCompletedExists = TaskAssignment::find()
                    ->where(['task_id' => $task->id, 'status' => TaskAssignment::STATUS_COMPLETED])
                    ->andWhere(['<>', 'id', $assignment->id])
                    ->exists();

                if (!$otherCompletedExists && $task->completed_at) {
                    $task->completed_at = null;
                    $task->save(false);
                }
            }
        }

        return [
            'success' => $assignment->save(false),
        ];
    }

}