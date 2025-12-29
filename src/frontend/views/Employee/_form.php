<?php

use common\models\ConstructionSite;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\Employee;
use common\models\User;

/** @var yii\web\View $this */
/** @var common\models\Employee $employee */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="employee-form">

    <?php $form = ActiveForm::begin(); ?>

    <h4>User</h4>

    <?= $form->field($user, 'username')->textInput(['maxlength' => true]) ?>

    <?= $form->field($user, 'email')->input('email') ?>

    <?= $form->field($user, 'password')->passwordInput()
        ->hint($user->isNewRecord 
            ? 'Leave empty to generate random' 
            : 'Leave empty to keep current password'
        ) ?>

    <?php if (
        Yii::$app->user->can('user.activate') || Yii::$app->user->can('user.activateLimited', ['model' => $employee]) ||
        Yii::$app->user->can('user.deactivate') || Yii::$app->user->can('user.deactivateLimited', ['model' => $employee]) ||
        Yii::$app->user->can('employee.update')
    ): ?>
        <?= $form->field($user, 'status')->dropDownList(
            User::statusList(),
            ['prompt' => 'Select status']
        ) ?>
    <?php else: ?>
        <?= $form->field($user, 'status')->textInput(['readonly' => true]) ?>
    <?php endif; ?>

    <hr>

    <h4>Employee</h4>

    <?= $form->field($employee, 'first_name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($employee, 'last_name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($employee, 'birth_date')->input('date') ?>

    <?= $form->field($employee, 'access_level')->dropDownList(
        $this->levels ?? ConstructionSite::getAccessLevels(), 
        [
            'prompt' => 'Select Access Level',
            'class' => 'form-control'
        ]
    ) ?>

    <?php if (Yii::$app->user->can('employee.update') || Yii::$app->user->can('employee.updateLimited', ['model' => $employee])): ?>
        <?= $form->field($employee, 'role')->dropDownList(
            User::getRoleList(),
            ['prompt' => 'Select role']
        ) ?>
    <?php else: ?>
        <?= $form->field($employee, 'role')->textInput(['readonly' => true]) ?>
    <?php endif; ?>

    <div class="form-group mt-3">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
