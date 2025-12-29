<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Employee $employee */

$this->title = 'Create Employee with user Account';
$this->params['breadcrumbs'][] = ['label' => 'Employees', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="employee-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'employee' => $employee,
        'user' => $user,
    ]) ?>

</div>
