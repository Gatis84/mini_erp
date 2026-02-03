<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use common\models\User;

/** @var yii\web\View $this */
/** @var common\models\User $user */

$this->title = 'User #' . $user->id;
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="user-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?php if (Yii::$app->user->can('user.deleteOther', ['targetUserId' => $user->id])): ?>
            <?= Html::a('Delete', ['delete', 'id' => $user->id], [
                'class' => 'btn btn-danger',
                'data' => ['confirm' => 'Confirm user delete?', 'method' => 'post'],
            ]) ?>
        <?php endif; ?>

        <?php if ((int)$user->status === User::STATUS_DELETED && Yii::$app->user->can('user.restore')): ?>
            <?= Html::a('Restore', ['restore', 'id' => $user->id], [
                'class' => 'btn btn-warning',
                'data' => ['confirm' => 'Restore this user?', 'method' => 'post'],
            ]) ?>
        <?php endif; ?>
    </p>

    <?= DetailView::widget([
        'model' => $user,
        'attributes' => [
            'id',
            'username',
            'email:email',
            'status',
            'created_at:datetime',
            'updated_at:datetime',
        ],
    ]) ?>
</div>
