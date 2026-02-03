<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use common\models\User;

/** @var yii\web\View $this */
/** @var common\models\Employee $employee */
/** @var common\models\User $user */
/** @var common\models\TaskAssignment $assignments */
/** @var common\models\ConstructionAssignment $csAssignments */

$this->title = 'Employee #' . $employee->id;
$this->params['breadcrumbs'][] = ['label' => 'Employees', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="employee-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $employee->id], ['class' => 'btn btn-success']) ?>
        <?php if (Yii::$app->user->can('user.deleteOther', ['targetUserId' => $user->id])): ?>
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
        <?php if ($user): ?>
            <?php if ((int)$user->status === User::STATUS_ACTIVE): ?>
                <?= Html::a(
                    'Deactivate', 
                    ['/user/user/deactivate', 'id' => $user->id],
                    [
                        'data' => [
                            'confirm' => 'Deactivate this employee?',
                            'method' => 'post',
                        ],
                        'class' => 'btn btn-danger'
                    ]
                ) ?>
            <?php elseif ((int)$user->status === User::STATUS_INACTIVE): ?>
                <?= Html::a(
                    'Activate',
                    ['/user/user/activate', 'id' => $user->id],
                    [
                        'data' => [
                            'confirm' => 'Activate this employee?',
                            'method' => 'post',
                        ],
                        'class' => 'btn btn-success'
                    ]
                ) ?>
            <?php endif; ?>
        <?php endif; ?>
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
            [
                'label' => 'Email',
                'value' => $user->email,
            ],
            'access_level',
            'role',
            'created_at:datetime',
            'updated_at:datetime',
        ],
    ]) ?>

    <div>
        <h5><strong>Assigned Tasks:</strong></h5>
        <?php if (empty($assignments)): ?>
            <p class="text-muted">No tasks assigned to this employee</p>
        <?php else: ?>
            <div class="row"> 
                <?php 
                $taskStatusClasses = 
                    [
                        'Draft' => 'bg-warning text-dark',
                        'Active' => 'bg-success',
                        'Archived' => 'bg-secondary',
                        'Cancelled' => 'bg-danger'
                    ];

                $assignmentsStatusesClasses = 
                [
                    'Assigned' => 'bg-secondary',
                    'In Progress' => 'bg-success',
                    'Completed' => 'bg-success',
                    'Overdue' => 'bg-danger',
                    'Cancelled' => 'bg-warning text-dark'
                ];

                foreach ($assignments as $assignment):
                    // dd($assignment,$assignment->status, $assignment->getStatusLabel(),  $assignment->task->status, $assignment->task->getStatusLabel());
                    $statusClass = $taskStatusClasses[$assignment->task->getStatusLabel()] ?? 'bg-secondary';
                    $assignmentClass = $assignmentsStatusesClasses[$assignment->getStatusLabel()] ?? 'bg-secondary'; ?>

                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                    <?= Html::encode($assignment->task->id . '. ' . $assignment->task->title) ?>
                                </h6>
                                <span class="badge <?= Html::encode($statusClass) ?>">
                                    <?= Html::encode($assignment->task->getStatusLabel()) ?>
                                </span>
                            </div>
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                    Employee Task Assignment status:
                                </h6>
                                <span class="badge <?= Html::encode($assignmentClass) ?> ">
                                    <?= Html::encode($assignment->getStatusLabel()) ?>
                                </span>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <?php if ($assignment->task->description): ?>
                                    <p class="card-text flex-grow-1">
                                        <?= Html::encode($assignment->task->description) ?>
                                    </p>
                                <?php endif; ?>
                                <div class="mt-auto">
                                    <?= Html::a('View Details', ['/task/task/view', 'id' => $assignment->task->id], ['class' => 'btn btn-sm btn-outline-primary w-100']) ?>
                                </div>
                            </div>
                        </div>
                    </div> 
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div>
        <h5><strong>Assigned Construction Sites:</strong></h5>
        <?php if (empty($csAssignments)): ?>
            <p class="text-muted">No Construction Sites assigned to this employee</p>
        <?php else: ?>
            <div class="row">
                <?php foreach ($csAssignments as $cs): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                    <?= Html::encode($cs->construction_site_id . '. ' . $cs->constructionSite->location ) ?>
                                </h6>
                            </div>
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                    Construction Site Assignment status:
                                </h6>
                                <span class="badge <?= Html::encode( $cs->active ? 'bg-success' : 'bg-secondary') ?> ">
                                    <?= $cs->active ? 'Active' : 'Inactive' ?>
                                </span>
                            </div>
                            <div class="card-body d-flex flex-column">
                                    <p class="card-text flex-grow-1">
                                        <?= Html::encode('Assigned at: ' . Yii::$app->formatter->asDatetime($cs->assigned_at)) ?>
                                    </p>
                                    <p class="card-text flex-grow-1">
                                        <?= Html::encode('Access level needed: ' . $cs->constructionSite->required_access_level) ?>
                                    </p>
                                <div class="mt-auto">
                                    <?= Html::a('View Details', ['/construction/construction-site/view', 'id' => $cs->construction_site_id], ['class' => 'btn btn-sm btn-outline-primary w-100']) ?>
                                </div>
                            </div>
                        </div>
                    </div> 
                <?php endforeach; ?>

            </div>
        <?php endif ?>
    </div>

</div>
