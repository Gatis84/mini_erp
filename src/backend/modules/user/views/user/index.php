<?php

use yii\helpers\Html;
use yii\grid\GridView;
use common\models\User;

/** @var yii\web\View $this */
/** @var common\models\UserSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Users';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="user-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            'id',
            'username',
            'email:email',
            [
                'attribute' => 'status',
                'filter' => [
                    User::STATUS_DELETED => 'Deleted',
                    User::STATUS_INACTIVE => 'Inactive',
                    User::STATUS_ACTIVE => 'Active',
                ],
                'value' => function ($model) {
                    return match ((int)$model->status) {
                        User::STATUS_DELETED => 'Deleted',
                        User::STATUS_INACTIVE => 'Inactive',
                        User::STATUS_ACTIVE => 'Active',
                        default => (string)$model->status,
                    };
                }
            ],
            'created_at:datetime',
            // 'updated_at:datetime',
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {delete} {restore}',
                'buttons' => [
                    'restore' => function ($url, $model) {
                        if ((int)$model->status !== User::STATUS_DELETED) {
                            return '';
                        }
                        if (!Yii::$app->user->can('user.restore')) {
                            return '';
                        }
                        return Html::a('Restore', ['restore', 'id' => $model->id], [
                            'data' => [
                                'confirm' => 'Restore this user?',
                                'method' => 'post',
                            ],
                        ]);
                    },
                    'delete' => function ($url, $model) {
                        if (!Yii::$app->user->can('user.deleteOther', ['targetUserId' => $model->id])) {
                            return '';
                        }
                        return Html::a('Delete', ['delete', 'id' => $model->id], [
                            'data' => [
                                'confirm' => 'Confirm user delete?',
                                'method' => 'post',
                            ],
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
</div>
