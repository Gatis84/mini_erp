<?php

use common\models\Employee;
use common\models\User;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use Yii;

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
            // ['class' => 'yii\grid\SerialColumn'], //uncomment to show row numbers

            'id' => [
                'attribute' => 'id',
                'label' => 'Employee ID',
                'value' => 'id',
            ],
            // 'user_id', no need to see if just sysAdmin can manage it
            'first_name',
            'last_name',
            'birth_date',
            'access_level',
            [
                'attribute' => 'role',
                'filter' => Employee::getRoleList(),
                'value' => function ($model) {
                    $roles = Employee::getRoleList();
                    return $roles[$model->role] ?? $model->role;
                }
            ],
            [
                'header' => 'Actions',
                'class' => ActionColumn::class,
                'template' => '{view} {update} {deactivate}',
                'buttons' => [
                    'deactivate' => function ($url, Employee $employee) {
                        $user = $employee->user;

                        if (!$user) {
                            return '';
                        }
                        
                        if ($employee->user_id === Yii::$app->user->id) {
                            return '';
                        }

                        $canDeactivate = Yii::$app->user->can('user.deactivate')
                        || Yii::$app->user->can('user.deactivateLimited', ['model' => $employee]);
                        $canActivate = Yii::$app->user->can('user.activate')
                        || Yii::$app->user->can('user.activateLimited', ['model' => $employee]);


                        if ((int)$user->status === User::STATUS_ACTIVE && $canDeactivate) {
                            return Html::a(
                                'Deactivate',
                                ['/employee/deactivate', 'id' => $employee->id],
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
                                ['/employee/activate', 'id' => $employee->id],
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
                'visibleButtons' => [  // for template buttons visibility
                    'update' => function ($model) {
                        return Yii::$app->user->can('employee.update') || Yii::$app->user->can('employee.updateLimited', ['model' => $model]);
                    },
                ],
                'urlCreator' => function ($action, Employee $employee) {
                    return Url::toRoute([$action, 'id' => $employee->id]);
                 }
            ],
        ],
    ]); ?>


</div>
