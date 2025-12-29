<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Task $model */
/** @var ActiveForm $form */
?>
<div class="Task">

    <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'description') ?>
        <?= $form->field($model, 'status') ?>
        <?= $form->field($model, 'updated_at') ?>
        <?= $form->field($model, 'construction_site_id') ?>
        <?= $form->field($model, 'title') ?>
        <?= $form->field($model, 'employee_id') ?>
        <?= $form->field($model, 'created_by') ?>
        <?= $form->field($model, 'created_at') ?>
    
        <div class="form-group">
            <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
        </div>
    <?php ActiveForm::end(); ?>

</div><!-- Task -->
