<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\Task $model */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Tasks', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
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
                        fn($a) => $a->employee->first_name . ' ' . $a->employee->last_name,
                        $model->assignments
                    ));
                },
            ],
            [
                'attribute' => 'status',
                'value' => fn($m) => $m->getStatusLabel(),
            ],
            [
                'label' => 'Created_by',
                'value' => function ($model) {
                    $creator = $model->getCreatorData();
                    return $creator ? 'ID:' . $creator['user_id'] . ' ' . $creator['first_name'] . ' ' . $creator['last_name'] : 'sysAdmin';
                },
            ],
            // use 'datetime' format to use Yii2 formatter settings from common/config/main.php
            // example in view: 2026-01-08 14:25:00.000 -> 2026-01-08 14:25
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
