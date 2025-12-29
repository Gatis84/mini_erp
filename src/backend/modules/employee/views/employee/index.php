<?php

use common\models\Employee;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use common\models\User;

/** @var yii\web\View $this */
/** @var common\models\EmployeeSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Employees';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="employee-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Employee', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            // ['class' => 'yii\grid\SerialColumn'],

            'id' => [
                'attribute' => 'id',
                'label' => 'Employee ID',
            ],
            'user_id',
            'first_name',
            'last_name',
            'birth_date' => [
                'attribute' => 'birth_date',
                'visible' => true,
            ],
            'access_level',
            ['attribute' => 'role',
            'filter' => Employee::getRoleList(),
            'value' => function ($model) {
                $roles = Employee::getRoleList();
                return $roles[$model->role] ?? $model->role;
            }],
            [
                'header' => 'Actions',
                'class' => ActionColumn::class,
                'template' => '{view} {update} {deactivate}',
                'buttons' => [
                    'deactivate' => function ($url, Employee $model) {

                        $user = $model->user;
                        if (!$user) {
                            return '';
                        }

                        $canDeactivate = Yii::$app->user->can('user.deactivate')
                            || Yii::$app->user->can('user.deactivateLimited', ['model' => $model]);

                        $canActivate = Yii::$app->user->can('user.activate')
                            || Yii::$app->user->can('user.activateLimited', ['model' => $model]);

                        if ((int)$user->status === User::STATUS_ACTIVE && $canDeactivate) {
                            return Html::a(
                                'Deactivate',
                                ['/user/user/deactivate', 'id' => $model->id],
                                [
                                    'data' => [
                                        'confirm' => 'Deactivate this employee?',
                                        'method' => 'post',
                                    ],
                                    'class' => 'text-danger',
                                ]
                            );
                        }

                        if ((int)$user->status === User::STATUS_INACTIVE && $canActivate) {
                            return Html::a(
                                'Activate',
                                ['/user/user/activate', 'id' => $model->id],
                                [
                                    'data' => [
                                        'confirm' => 'Activate this employee?',
                                        'method' => 'post',
                                    ],
                                    'class' => 'text-success',
                                ]
                            );
                        }

                        return '';
                    },
                ],
                'urlCreator' => function ($action, Employee $model) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                 }
            ],
        ],
    ]); ?>


</div>
