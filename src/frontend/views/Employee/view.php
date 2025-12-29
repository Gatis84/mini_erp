<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\Employee $employee */

$this->title = $employee->id;
$this->params['breadcrumbs'][] = ['label' => 'Employees', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="employee-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?php if (Yii::$app->user->can('employee.update') || Yii::$app->user->can('employee.updateLimited', ['model' => $employee])): ?>
            <?= Html::a('Update', ['update', 'id' => $employee->id], ['class' => 'btn btn-primary']) ?>
        <?php endif; ?>

        <?php $user = $employee->user; ?>
        <?php if ($user && (int)$user->status === \common\models\User::STATUS_ACTIVE && (Yii::$app->user->can('user.deactivate') || Yii::$app->user->can('user.deactivateLimited', ['model' => $employee]))): ?>
            <?= Html::a('Deactivate', ['deactivate', 'id' => $employee->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'Deactivate this employee?',
                    'method' => 'post',
                ],
            ]) ?>
        <?php endif; ?>

        <?php if ($user && (int)$user->status === \common\models\User::STATUS_INACTIVE && (Yii::$app->user->can('user.activate') || Yii::$app->user->can('user.activateLimited', ['model' => $employee]))): ?>
            <?= Html::a('Activate', ['activate', 'id' => $employee->id], [
                'class' => 'btn btn-success',
                'data' => [
                    'confirm' => 'Activate this employee?',
                    'method' => 'post',
                ],
            ]) ?>
        <?php endif; ?>

        <?= Html::a('Delete', ['delete', 'id' => $employee->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $employee,
        'attributes' => [
            'id',
            'user_id',
            'first_name',
            'last_name',
            'birth_date',
            'access_level',
            'role',
            'created_at',
            'updated_at',
        ],
    ]) ?>

</div>
