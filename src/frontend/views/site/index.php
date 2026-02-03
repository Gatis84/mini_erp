<?php
/** @var yii\web\View $this */

use yii\bootstrap5\Html;

$this->title = 'WorkSys FE - Job Management';
?>

<div class="site-index">
    <div class="jumbotron text-center bg-secondary bg-gradient-secondary green text-white py-5 mb-5">
        <h1 class="display-4">WorkSys SysAdmin</h1>
        <p class="lead">Manage Users, Admins, Employees, Construction Sites & Tasks</p>
        <a class="btn btn-light btn-lg" href="<?= Yii::$app->params['backendUrl'] ?>/index.php">Login to Backend</a>
    </div>

    <div class="row mb-5">
        <div class="col-md-3">
            <div class="card text-center border-primary">
                <div class="card-body">
                    <h2><?php echo \common\models\Employee::find()->count(); ?></h2>
                    <p>Employees</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-success">
                <div class="card-body">
                    <h2><?php echo \common\models\ConstructionSite::find()->count(); ?></h2>
                    <p>Total Construction Sites</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-info">
                <div class="card-body">
                    <h2><?php echo \common\models\Task::find()->count(); ?></h2>
                    <p>tasks</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-warning">
                <div class="card-body">
                    <h2>Applicant`s</h2>
                    <p><a style="color: black !important;" href="https://www.linkedin.com/in/gatispaurans/" target="_blank">Linkedin</a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <h3>Employee Management</h3>
            <p>CRUD for employees with RBAC roles (admin/teamLead/employee). Track access levels and assignments.</p>
            <?= Html::a('→ Access Module', ['/employee/index'], ['class' => 'btn btn-outline-primary', 'color']) ?>
        </div>
        <div class="col-lg-4">
            <h3>Construction Sites</h3>
            <p>Manage sites with required access levels. Link tasks to specific locations.</p>
            <?= Html::a('→ Access Module', ['/construction-site/index'], ['class' => 'btn btn-outline-success']) ?>
        </div>
        <div class="col-lg-4">
            <h3>Task Management</h3>
            <p>Assign tasks to employees/sites with status tracking and due dates.</p>
            <?= Html::a('→ Access Module', ['/task/index'], ['class' => 'btn btn-outline-info']) ?>
        </div>
    </div>

    <div id="christmas-banner" class="christmas-banner-wrapper">
        <div class="christmas-banner">
            🎄 Merry Christmas and Happy New Year 2026! 🎄
        </div>
        <button type="button" class="christmas-banner-close" aria-label="Close">
            ×
        </button>
    </div>

</div>