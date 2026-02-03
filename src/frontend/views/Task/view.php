<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\Task $model */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Tasks', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

$updateUrl = Url::to(['task-assignment/update-status-ajax']);

$js = <<<JS
$('.assignment-status').on('change', function () {
    const el = $(this);

    $.post('$updateUrl', {
        id: el.data('id'),
        status: el.val(),
        _csrf: yii.getCsrfToken()
    }).done(function (res) {
        if (res && res.success) {
            location.reload();
        } else {
            alert('Status update failed');
        }
    }).fail(function () {
        alert('Status update failed');
    });
});
JS;

$this->registerJs($js);

?>

<div class="task-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'construction_site_id',
            'title',
            'description',
           [
                'label' => 'Assigned Employees',
                'value' => function ($model) {
                    return implode(', ', array_map(
                        fn($a) => $a->employee->first_name . ' ' . $a->employee->last_name . '(Task Assignment status: ' . $a->getStatusLabel() . ')',
                        $model->assignments
                    ));
                },
            ],
            [
                'attribute' => 'status',
                'label' => 'Task status',
                'value' => fn($m) => $m->getStatusLabel(),
            ],
            [
                'label' => 'My Task Assignment Status',
                'format' => 'raw',
                'value' => function ($model) {

                    $employee = \common\models\Employee::findOne([
                        'user_id' => Yii::$app->user->id
                    ]);

                    if (!$employee) {
                        return 'N/A';
                    }

                    $assignment = \common\models\TaskAssignment::findOne([
                        'task_id' => $model->id,
                        'employee_id' => $employee->id,
                    ]);

                    if (!$assignment) {
                        return 'Not assigned';
                    }

                    if (!Yii::$app->user->can('taskAssignment.updateOwnStatus', [
                        'model' => $assignment,
                    ])) {
                        return $assignment->getStatusLabel();
                    }

                    return Html::dropDownList(
                        'assignment_status',
                        $assignment->status,
                        $assignment->statusList(),
                        [
                            'class' => 'form-control assignment-status',
                            'data-id' => $assignment->id,
                            'style' => 'width:180px',
                        ]
                    );
                },
            ],
            [
                'label' => 'Created by',
                'value' => function ($model) {
                    $creator = $model->getCreatorData();
                    return $creator ? 'ID:' . $creator['user_id'] . ' ' . $creator['first_name'] . ' ' . $creator['last_name'] : 'sysAdmin';
                },
            ],
            'created_at:datetime',
            'updated_at:datetime',
            [
                'label' => 'Planned Start At',
                'value' => function ($model) {
                    return $model->planned_start_at ? Yii::$app->formatter->asDatetime($model->planned_start_at) : 'Not set';
                },
            ],
            [
                'label' => 'Planned End At',
                'value' => function ($model) {
                    return $model->planned_end_at ? Yii::$app->formatter->asDatetime($model->planned_end_at) : 'Not set';
                },

            ],
            [
                'label' => 'Completed At',
                'value' => function ($model) {
                    return $model->completed_at ? Yii::$app->formatter->asDatetime($model->completed_at) : 'Not completed';
                },
            ]
        ],
    ]) ?>

</div>
