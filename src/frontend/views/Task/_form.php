<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use common\models\Employee;
use common\models\User;
use common\models\Task;
use common\models\TaskAssignment;
use common\models\ConstructionSite;

/** @var yii\web\View $this */
/** @var common\models\Task $model */
/** @var yii\widgets\ActiveForm $form */

$constructionSites = ArrayHelper::map(
    ConstructionSite::find()->all(),
    'id',
    fn($cs) => 'id:' . $cs->id . ' ' . $cs->location
);

$employees = ArrayHelper::map(
    // Employee::find()->where(['status' => 10])->all(),
    Employee::find()->all(),
    'id',
    fn($e) => $e->first_name . ' ' . $e->last_name
);

$assignment = $assignment ?? new TaskAssignment();


?>

<div class="task-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->errorSummary([$model, $assignment]) ?>

    <?= $form->field($model, 'construction_site_id')->dropDownList(
        $constructionSites,
        ['prompt' => 'Select Construction Site']
    ) ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'description')->textInput() ?>

    <?= $form->field($assignment, 'employee_ids')->listBox(
        $employees,
        [
            'multiple' => true,
            'size' => 10,
            'required' => true,
        ]
    ) ?>

    <?= $form->field($model, 'status')->dropDownList(
        Task::statusList()
    ) ?>

    <!-- <?= $form->field($model, 'created_by')->textInput() ?>

    <?= $form->field($model, 'created_at')->textInput() ?>

    <?= $form->field($model, 'updated_at')->textInput() ?> -->

        <?= $form->field($model, 'planned_start_at')->input('datetime-local', [
        'value' => $model->planned_start_at ? date('Y-m-d\TH:i', strtotime($model->planned_start_at)) : null
    ]) ?>

    <?= $form->field($model, 'planned_end_at')->input('datetime-local', [
        'value' => $model->planned_end_at ? date('Y-m-d\TH:i', strtotime($model->planned_end_at)) : null
    ]) ?>
    
    <?= $form->field($model, 'completed_at')->input('datetime-local', [
        'value' => $model->completed_at ? date('Y-m-d\TH:i', strtotime($model->completed_at)) : null
    ]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
