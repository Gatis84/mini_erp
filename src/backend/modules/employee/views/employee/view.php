<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use common\models\User;

/** @var yii\web\View $this */
/** @var common\models\Employee $employee */
/** @var common\models\User $user */

$this->title = 'Employee #' . $employee->id;
$this->params['breadcrumbs'][] = ['label' => 'Employees', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="employee-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $employee->id], ['class' => 'btn btn-success']) ?>
        <?php if (Yii::$app->user->can('user.delete')): ?>
            <?= Html::a(
                'Delete',
                ['delete', 'id' => $user->id],
                [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => 'Confirm Employee delete?',
                        'method' => 'post',
                    ],
                ]
            ) ?>
        <?php endif; ?>
        <!-- <?php if (
            $user->status == User::STATUS_DELETED &&
            Yii::$app->user->can('user.restore')
        ): ?>
            <?= Html::a(
                'Restore user',
                ['restore', 'id' => $user->id],
                [
                    'class' => 'btn btn-warning',
                    'data' => [
                        'confirm' => 'Restore this user?',
                        'method' => 'post',
                    ],
                ]
            ) ?>
        <?php endif; ?> -->
        <?= Html::a('Open User', ['/user/user/view', 'id' => $user->id], ['class' => 'btn btn-warning']) ?>

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
            // 'status',
            'created_at',
            'updated_at',
        ],
    ]) ?>

</div>
