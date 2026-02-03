<?php

namespace backend\modules\construction\controllers;

use common\models\ConstructionSite;
use common\models\ConstructionSiteSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use Yii;
use common\models\Employee;
use common\models\User;
use common\models\ConstructionAssignment;

/**
 * ConstructionSiteController implements the CRUD actions for ConstructionSite model.
 */
class ConstructionSiteController extends Controller
{
    /**
     * @inheritDoc
     */

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
                            'actions' => ['index', 'view', 'create', 'update', 'delete'],
                            'roles' => ['sysAdmin'],
                        ],
                    ],
                    'denyCallback' => function ($rule, $action) {
                        $auth = Yii::$app->authManager;
                        $userId = Yii::$app->user->id;
                        
                        // Current user roles
                        $userRoles = array_keys($auth->getRolesByUser($userId));
                        
                        // Get required role from current rule
                        $requiredRole = 'unknown';
                
                        if ($rule && $rule->roles && !empty($rule->roles)) {
                            $requiredRole = implode(', ', $rule->roles);
                        }

                        $message = "Access denied!\n";
                        $message .= "Action: " . $action->id . "\n";
                        $message .= "Required role: {$requiredRole}\n";
                        $message .= "Your roles: " . (empty($userRoles) ? 'none' : implode(', ', $userRoles));
                        
                        throw new \yii\web\ForbiddenHttpException($message);
                    }
                ],
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all ConstructionSite models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new ConstructionSiteSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single ConstructionSite model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        $CAs_data = ConstructionAssignment::find()
        ->select(['employee_id', 'assigned_at', 'reassigned_at', 'active'])
        ->where(['construction_site_id' => $model->id])
        ->asArray()
        ->all();

        $model->teamLeadAssignments = [];

        foreach ($CAs_data as $ca) {
            $model->teamLeadAssignments[(int)$ca['employee_id']] = [
                'assigned_at' => $ca['assigned_at'],
                'reassigned_at' => $ca['reassigned_at'],
                'active' => (bool)$ca['active'],
            ];
        }

        $employees = Employee::find()
        ->where(['id' => array_keys($model->teamLeadAssignments)])
        ->indexBy('id')
        ->all();

        return $this->render('view', [
            'model' => $model,
            'employees' => $employees,
        ]);

    }

    public function actionCreate()
    {

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

    /**
     * Updates an existing ConstructionSite model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
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
            ->where(['construction_site_id' => $id, 'active' => 1,])
            ->column();

        $assignment = new ConstructionAssignment();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            
            $newIds = is_array($model->teamLeadIds) ? $model->teamLeadIds : [];

            $newIds = array_values(array_unique(array_map('intval', $newIds)));

            // Get all current Assignments (active=1,active=0)
                $existingRows = ConstructionAssignment::find()
                    ->select(['employee_id', 'active', 'assigned_at', 'ended_at'])
                    ->where(['construction_site_id' => $model->id])
                    ->asArray()
                    ->all();

                $existingAll = [];
                $activeIds = [];
                $inactiveIds = [];

                foreach ($existingRows as $row) {
                    $eid = (int)$row['employee_id'];
                    $existingAll[$eid] = $row;

                    if ((int)$row['active'] === 1) {
                        $activeIds[] = $eid;
                    } else {
                        $inactiveIds[] = $eid;
                    }
                }

                $activeIds = array_values(array_unique($activeIds));
                $inactiveIds = array_values(array_unique($inactiveIds));

                // Added
                $toInsert = array_diff($newIds, array_keys($existingAll));

                // Was Inactive, now need to Reactivate
                $toReactivate = array_intersect($newIds, $inactiveIds);

                // Was active, but now not chosen, so Deactivate them
                $toDeactivate = array_diff($activeIds, $newIds);

                $tx = Yii::$app->db->beginTransaction();
                try {
                    // INSERT new ones
                    foreach ($toInsert as $employeeId) {
                        $ca = new ConstructionAssignment();
                        $ca->construction_site_id = $model->id;
                        $ca->employee_id = (int)$employeeId;
                        $ca->active = 1;
                        // assigned_at default GETDATE()
                        $ca->save(false);
                    }

                    // REACTIVATE (with saved previous assigned_at)
                    if (!empty($toReactivate)) {
                        ConstructionAssignment::updateAll(
                            [
                                'active' => 1,
                                'reassigned_at' => new \yii\db\Expression('GETDATE()'),
                                'ended_at' => null,
                            ],
                            [
                                'construction_site_id' => $model->id,
                                'employee_id' => $toReactivate,
                            ]
                        );
                    }

                    // DEACTIVATE
                    if (!empty($toDeactivate)) {
                        ConstructionAssignment::updateAll(
                            [
                                'active' => 0,
                                'ended_at' => new \yii\db\Expression('GETDATE()'),
                            ],
                            [
                                'construction_site_id' => $model->id,
                                'employee_id' => $toDeactivate,
                            ]
                        );
                    }

                    $tx->commit();
                } catch (\Throwable $e) {
                    $tx->rollBack();
                    throw $e;
                }

            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
            'teamLeads' => $teamLeads,
            'assignment' => $assignment
        ]);
    }

    /**
     * Deletes an existing ConstructionSite model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the ConstructionSite model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return ConstructionSite the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = ConstructionSite::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
