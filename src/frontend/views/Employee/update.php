<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Employee $employee */

$this->title = 'Update Employee: ' . $employee->id;
$this->params['breadcrumbs'][] = ['label' => 'Employees', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $employee->id, 'url' => ['view', 'id' => $employee->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="employee-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'employee' => $employee,
        'user' => $user,
    ]) ?>

</div>
