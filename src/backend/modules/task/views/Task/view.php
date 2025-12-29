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
            'created_by',
            'created_at',
            'updated_at',
        ],
    ]) ?>

</div>
