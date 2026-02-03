<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use common\models\Employee;
use yii\web\YiiAsset;

/** @var yii\web\View $this */
/** @var common\models\ConstructionSite $model */

$this->title = 'Construction Site: ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Construction Sites', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
YiiAsset::register($this);

?>

<div class="construction-site-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'location',
            'area_m2',
            'required_access_level',
            'created_at:datetime',
            'updated_at:datetime',
        ],
    ]) ?>

    <div class="row">
        <div class="col-md-12">
            <h5><strong>Assigned Team Leads:</strong></h5>
            <?php if (empty($model->teamLeadAssignments)): ?>
                <p class="text-muted">No team leads assigned</p>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($model->teamLeadAssignments as $employeeId => $data):
                        $employee = Employee::findOne($employeeId);
                        if ($employee): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="card-title mb-0">
                                            <?= Html::encode($employeeId . '. ' . $employee->first_name . ' ' . $employee->last_name) ?>
                                        </h6>
                                        <span class="badge <?= $data['active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?= $data['active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <p class="card-text flex-grow-1">
                                            <?= Html::tag('span', 'Assigned at: ' . Yii::$app->formatter->asDatetime($data['assigned_at'])) ?>
                                        </p>
                                        <p class="card-text flex-grow-1">
                                            <?php if ($data['reassigned_at']): ?>
                                                <?= HTML::tag('span', 'ReAssigned at: ' . Yii::$app->formatter->asDatetime($data['reassigned_at'])) ?>
                                            <?php endif; ?>
                                        </p>
                                        <div class="mt-auto">
                                            <?= Html::a('View Details', ['/employee/employee/view', 'id' => $employeeId], ['class' => 'btn btn-sm btn-outline-primary w-100']) ?>
                                        </div>   
                                    </div>
                                </div>
                            </div>
                        <?php endif; endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <h5><strong>Assigned Tasks:</strong></h5>
        <?php if (empty($model->tasks)): ?>
            <p class="text-muted">No tasks assigned to this construction site</p>
        <?php else: ?>
            <div class="row"> 
                <?php 
                $statusClasses = 
                    [
                        'Draft' => 'bg-warning text-dark',
                        'Active' => 'bg-success',
                        'Archived' => 'bg-secondary',
                        'Cancelled' => 'bg-danger'
                    ];

                foreach ($model->tasks as $task):
                    $statusClass = $statusClasses[$task->getStatusLabel()] ?? 'bg-secondary'; ?>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                    <?= Html::encode($task->id . '. ' . $task->title) ?>
                                </h6>
                                <span class="badge <?= Html::encode($statusClass) ?>">
                                    <?= Html::encode($task->getStatusLabel()) ?>
                                </span>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <?php if ($task->description): ?>
                                    <p class="card-text flex-grow-1">
                                        <?= Html::encode($task->description) ?>
                                    </p>
                                <?php endif; ?>
                                <div class="mt-auto">
                                    <?= Html::a('View Details', ['/task/task/view', 'id' => $task->id], ['class' => 'btn btn-sm btn-outline-primary w-100']) ?>
                                </div>
                            </div>
                        </div>
                    </div> <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>
