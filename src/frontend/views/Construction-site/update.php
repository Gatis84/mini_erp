<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\ConstructionSite $model */

$this->title = 'Update Construction Site: ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Construction Sites', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="construction-site-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'teamLeads' => $teamLeads,
        'assignment' => $assignment,
    ]) ?>

</div>
