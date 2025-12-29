<?php

namespace frontend\controllers;

use common\models\ConstructionSiteSearch;
use Yii;
use yii\web\Controller; 
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use common\models\ConstructionSite;
use yii\web\NotFoundHttpException;
use common\models\Employee;
use common\models\User;
use common\models\ConstructionAssignment;

class ConstructionSiteController extends Controller
{

    protected  $levels;

    public function init()
    {
        parent::init();
        $this->levels = ConstructionSite::getAccessLevels();
    }
    
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        [
                            'allow' => true,
                            // 'roles' => ['project.view'], 
                            'roles' => ['@'], // Logged-in users only
                        ],
                    ],
                ],
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ],
        );
    }

    public function actionIndex()
    {
        if (!Yii::$app->user->can('project.view')) {
            throw new ForbiddenHttpException('You do not have permission to view construction sites.');
        }

        $searchModel = new ConstructionSiteSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);

        if (
            Yii::$app->user->can('project.view')
            || Yii::$app->user->can('project.viewOwn', ['model' => $model])
        ) {
            $model->teamLeadIds = ConstructionAssignment::find()
                ->select('employee_id')
                ->where(['construction_site_id' => $id])
                ->column();

            return $this->render('view', [
                'model' => $model,
            ]);
        }

        throw new ForbiddenHttpException();
    }

    public function actionCreate()
    {
        if (!Yii::$app->user->can('project.create')) {
            throw new ForbiddenHttpException();
        }

        
        $teamLeads = Employee::find()
            ->alias('e')
            ->innerJoin('user u', 'u.id = e.user_id')
            ->where([
                'e.role'   => 'teamLead',
                'u.status' => User::STATUS_ACTIVE,
            ])
            ->select([
                "CONCAT(e.first_name,' ',e.last_name) AS name",
                'e.id',
            ])
            ->indexBy('e.id')
            ->column();
        
        $model = new ConstructionSite();
        $assignment = new ConstructionAssignment();


        if ($model->load(Yii::$app->request->post())) {
        // Load teamLeadIds even if main validation fails
            if (Yii::$app->request->post('ConstructionSite')['teamLeadIds'] ?? false) {
                $model->teamLeadIds = Yii::$app->request->post('ConstructionSite')['teamLeadIds'];
            }
            
            if ($model->save()) {
                ConstructionAssignment::deleteAll([
                    'construction_site_id' => $model->id,
                ]);

                if (!empty($model->teamLeadIds)) {
                    foreach ($model->teamLeadIds as $employeeId) {
                        $ca = new ConstructionAssignment();
                        $ca->construction_site_id = $model->id;
                        $ca->employee_id = $employeeId;
                        $ca->save(false);
                    }
                }

                return $this->redirect(['view', 'id' => $model->id]);
            }
        }
        
        return $this->render('create', [
            'model' => $model,
            'assignment' => $assignment,
            'teamLeads' => $teamLeads,
        ]);
    }

    public function actionUpdate($id)
    {
        
        if (!Yii::$app->user->can('project.update')) {
            throw new ForbiddenHttpException();
        }

        $teamLeads = Employee::find()
            ->alias('e')
            ->innerJoin('user u', 'u.id = e.user_id')
            ->where([
                'e.role'   => 'teamLead',
                'u.status' => User::STATUS_ACTIVE,
            ])
            ->select([
                "CONCAT(e.first_name,' ',e.last_name) AS name",
                'e.id',
            ])
            ->indexBy('e.id')
            ->column();

        $model = $this->findModel($id);

        $model->teamLeadIds = ConstructionAssignment::find()
        ->select('employee_id')
        ->where(['construction_site_id' => $id])
        ->column();

        $assignment = new ConstructionAssignment();


        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            ConstructionAssignment::deleteAll([
                    'construction_site_id' => $model->id,
                ]);

            $ids = is_array($model->teamLeadIds) ? $model->teamLeadIds : [];


            foreach ($ids as $employeeId) {
                $ca = new ConstructionAssignment();
                $ca->construction_site_id = $model->id;
                $ca->employee_id = $employeeId;
                $ca->save(false);
            }

            return $this->redirect(['view', 'id' => $model->id]);

        }

        return $this->render('update', [
            'model' => $model,
            'teamLeads' => $teamLeads,
            'assignment' => $assignment,
        ]);
    }

    public function actionDelete($id)
    {
        if (!Yii::$app->user->can('project.delete')) {
            throw new ForbiddenHttpException();
        }

        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = ConstructionSite::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }

}