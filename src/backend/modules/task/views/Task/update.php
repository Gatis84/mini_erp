<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Task $model */
/** @var common\models\TaskAssignment $assignment */

$this->title = 'Update Task: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Tasks', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="task-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'assignment' => $assignment,
    ]) ?>

</div>
