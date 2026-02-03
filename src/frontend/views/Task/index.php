<?php

use common\models\Task;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use common\models\ConstructionSite;
use common\models\Employee;

/** @var yii\web\View $this */
/** @var common\models\TaskSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Tasks';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="task-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Task', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'id',
            [
                'attribute' => 'construction_site_id',
                'value' => fn(Task $model) => $model->constructionSite->id ?? '-',
                'filter' => ArrayHelper::map(
                    ConstructionSite::find()->all(),
                    'id',
                    'id'
                ),
            ],
            'title',
            'description',
            [
                'attribute' => 'employee_id',
                'label' => 'Assigned Employees',
                'value' => function ($model) {
                    return implode(', ', array_map(
                        fn($a) => $a->employee->first_name . ' ' . $a->employee->last_name,
                        $model->assignments
                    ));
                },
                'filter' => ArrayHelper::map(Employee::find()->all(), 'id', function($e) {
                    return $e->id . '. ' . $e->first_name . ' ' . $e->last_name;
                 }),
            ],
            [
                'attribute' => 'status',
                'value' => fn(Task $model) => $model->getStatusLabel(),
                'filter' => Task::statusList(),
            ],
            //'created_by',
            //'created_at',
            //'updated_at',
            [
                'class' => ActionColumn::class,
                'urlCreator' => function ($action, Task $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                 }
            ],
        ],
    ]); ?>


</div>
