<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\ConstructionSite;
use common\assets\DualSelectAsset;

/** @var yii\web\View $this */
/** @var common\models\ConstructionSite $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="construction-site-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'location')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'area_m2')->textInput() ?>

    <?= $form->field($model, 'required_access_level')->dropDownList(
        $this->levels ?? ConstructionSite::getAccessLevels(), 
        [
            'prompt' => 'Select Access Level',
            'class' => 'form-control'
        ]
    ) ?>

    <div class="row">

        <div class="col-md-5">
            <label class="control-label">Available Team Leads</label>
            <input type="hidden" name="ConstructionSite[teamLeadIds]" value="">
            <select
                multiple
                id="teamLeadsAvailable"
                class="form-control"
                size="12"
                style="height:300px"
            >
                <?php foreach ($teamLeads as $id => $name): ?>
                    <option value="<?= $id ?>"><?= Html::encode($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2 d-flex align-items-center justify-content-center">
            <div class="btn-group-vertical">
                <?= Html::button('›',  ['class' => 'btn btn-outline-primary', 'id' => 'addOne']) ?>
                <?= Html::button('≫',  ['class' => 'btn btn-outline-primary', 'id' => 'addAll']) ?>
                <?= Html::button('‹',  ['class' => 'btn btn-outline-primary', 'id' => 'removeOne']) ?>
                <?= Html::button('≪',  ['class' => 'btn btn-outline-primary', 'id' => 'removeAll']) ?>
            </div>
        </div>

        <div class="col-md-5">
            <label class="control-label">Selected Team Leads</label>
            <select
                multiple
                id="teamLeadsSelected"
                class="form-control"
                name="ConstructionSite[teamLeadIds][]"
                size="12"
                style="height:300px"
            >
            </select>
        </div>

    </div>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    <?php

        DualSelectAsset::register($this);

        $existing = $model->teamLeadIds ?? [];

        $this->registerJs(
            'initDualSelect({
                available: "#teamLeadsAvailable",
                selected:  "#teamLeadsSelected",
                addOne:    "#addOne",
                addAll:    "#addAll",
                removeOne: "#removeOne",
                removeAll: "#removeAll",
                form:      "form",
                existing:  ' . json_encode($existing) . '
            });'
        );
    ?>
</div>
