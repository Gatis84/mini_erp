<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use common\models\Employee;
use yii\web\YiiAsset;

/** @var yii\web\View $this */
/** @var common\models\ConstructionSite $model */

$this->title = 'Construction Site: ' .$model->id;
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
                <?php if (empty($model->teamLeadIds)): ?>
                    <p class="text-muted">No team leads assigned</p>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($model->teamLeadIds as $employeeId): 
                            $employee = Employee::findOne($employeeId);
                            if ($employee):
                        ?>
                            <div class="col-md-4 mb-2">
                                <div class="card bg-light">
                                    <div class="card-body p-3">
                                        <h6 class="card-title mb-1"><?= Html::encode( $employeeId . '. ' . $employee->first_name . ' ' . $employee->last_name) ?></h6>
                                    </div>
                                </div>
                            </div>
                        <?php endif; endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>


</div>
