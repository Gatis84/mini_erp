<?php
/** @var yii\web\View $this */

use yii\bootstrap5\Html;

$this->title = 'ElvaTask BE - Job Management Admin';
?>

<div class="site-index">
    <div class="jumbotron text-center bg-secondary bg-gradient-secondary green text-white py-5 mb-5">
        <h1 class="display-4">Welcome to</h1>
        <h1 class="display-4">ElvaTask Backend sysAdmin</h1>
        <p class="lead">Manage Users, Employees, Construction Sites & Tasks</p>
        <p class="lead">with full permissions</p>
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
                    <p><a class="" href="https://www.linkedin.com/in/gatispaurans" target="_blank">Linkedin</a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 d-flex">
            <div class="card w-100 d-flex flex-column">
                <div class="card-body d-flex flex-column justify-content-center align-items-center text-center">
                    <h3>Employee Management</h3>
                    <p>CRUD for employees with RBAC roles (admin/teamLead/employee). Manage access levels and assignments.</p>
                    <div class="mt-auto">
                        <?= Html::a('→ Access Module', ['/employee/employee'], ['class' => 'btn btn-outline-primary']) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 d-flex">
            <div class="card w-100 d-flex flex-column">
                <div class="card-body d-flex flex-column justify-content-center align-items-center text-center">
                    <h3>Construction Sites</h3>
                    <p>Manage sites with required access levels. Link tasks to specific locations.</p>
                    <div class="mt-auto">
                        <?= Html::a('→ Access Module', ['/construction/construction-site'], ['class' => 'btn btn-outline-success']) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 d-flex">
            <div class="card w-100 d-flex flex-column">
                <div class="card-body d-flex flex-column justify-content-center align-items-center text-center">
                    <h3>Task Management</h3>
                    <p>Assign tasks to employees/sites with status tracking and due dates.</p>
                    <div class="mt-auto">
                        <?= Html::a('→ Access Module', ['/task/task'], ['class' => 'btn btn-outline-info']) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 d-flex">
            <div class="card w-100 d-flex flex-column">
                <div class="card-body d-flex flex-column justify-content-center align-items-center text-center">
                    <h3>Users Management</h3>
                    <p>Delete user or restore deleted employee</p>
                    <div class="mt-auto">
                        <?= Html::a('→ Access Module', ['/user/user'], ['class' => 'btn btn-outline-warning']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
