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
/** @var common\models\TaskAssignment $assignment */

$employees = ArrayHelper::map(
    // Employee::find()->where(['status' => 10])->all(),
    Employee::find()->all(),
    'id',
    fn($e) => $e->first_name . ' ' . $e->last_name
);

$constructionSites = ArrayHelper::map(
    ConstructionSite::find()->all(),
    'id',
    fn($cs) => 'id:' . $cs->id . ' ' . $cs->location
);

$taskCreators = ArrayHelper::map(
    Employee::find()
        ->where(['user_id' => Employee::gettaskCreators()])
        ->all(),
    'user_id',
    function ($employee) {
        return 'id:' . $employee->user_id . ' ' . 
               $employee->first_name . ' ' . $employee->last_name;
    }
);


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

    <!-- <?= $form->field($assignment, 'status')
        ->hiddenInput(['value' => TaskAssignment::STATUS_ASSIGNED])->label(false) ?> -->

    <?= $form->field($model, 'status')
        ->dropDownList(Task::statusList()) ?>

    <!-- <?= $form->field($model, 'created_by')->dropDownList($taskCreators, ['prompt' => 'Select Task Creator']) ?> -->

    <!-- <?= $form->field($model, 'created_at')->textInput(['readonly' => true, 'class' => 'form-control']) ?> -->

    <!-- <?= $form->field($model, 'updated_at')->textInput(['readonly' => true, 'class' => 'form-control']) ?> -->


    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
