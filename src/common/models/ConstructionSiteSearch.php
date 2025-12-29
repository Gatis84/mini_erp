<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\ConstructionSite;
use Yii;

/**
 * ConstructionSiteSearch represents the model behind the search form of `common\models\ConstructionSite`.
 */
class ConstructionSiteSearch extends ConstructionSite
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'area_m2', 'required_access_level'], 'integer'],
            [['location', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @param string|null $formName Form name to be used into `->load()` method.
     *
     * @return ActiveDataProvider
     */
    public function search($params, $formName = null)
    {
        $query = ConstructionSite::find()->alias('cs');

        /**
         * BACKEND SYSADMIN
         * can see all construction sites
         */
        if (Yii::$app->id === 'app-backend' && Yii::$app->user->can('sysAdmin')) {
            return $this->buildProvider($query, $params, $formName);
        }

        /**
         * FRONTEND — ADMIN
         * can see all construction sites
         */
        if (Yii::$app->user->can('project.create')) {
            return $this->buildProvider($query, $params, $formName);
        }

        /**
         * FRONTEND — TEAM LEAD
         * can see construction sites where they are assigned to tasks
         */

        if (Yii::$app->user->can('project.view')
            && !Yii::$app->user->can('project.viewOwn')) {
            $employee = Employee::findOne(['user_id' => Yii::$app->user->id]);

            if (!$employee) {
                $query->andWhere('0=1');
                return $this->buildProvider($query, $params, $formName);
            }

            // Subquery to get construction site IDs where the employee has assignments
            $assignedSites = (new \yii\db\Query())
                ->select('ca.construction_site_id')
                ->distinct()
                ->from(['ca' => 'construction_assignment'])
                ->where(['ca.employee_id' => $employee->id]);

                // Subquery to get construction site IDs where the employee has tasks assigned
                 $taskSites = (new \yii\db\Query())
                    ->select('t.construction_site_id')
                    ->distinct()
                    ->from(['ta' => 'task_assignment'])
                    ->innerJoin(['t' => 'task'], 't.id = ta.task_id')
                    ->where(['ta.employee_id' => $employee->id]);

                    $union = (new \yii\db\Query())
                        ->select('construction_site_id')
                        ->from(['u' => $assignedSites->union($taskSites)]);

                    $query->andWhere(['cs.id' => $union]);


                return $this->buildProvider($query, $params, $formName);
            }



        /**
         * FRONTEND — EMPLOYEE
         * can only see construction sites where they have tasks assigned
         */
        if (Yii::$app->user->can('project.viewOwn')) {

            $subQuery = (new \yii\db\Query())
                ->select('t.construction_site_id')
                ->distinct()
                ->from(['ta' => 'task_assignment'])
                ->innerJoin(['t' => 'task'], 't.id = ta.task_id')
                ->innerJoin(['e' => 'employee'], 'e.id = ta.employee_id')
                ->where(['e.user_id' => Yii::$app->user->id]);

            $query->andWhere(['cs.id' => $subQuery]);
            
            return $this->buildProvider($query, $params, $formName);
        }

        $query->andWhere('0=1');
        return $this->buildProvider($query, $params, $formName);
    }


    protected function buildProvider($query, $params, $formName)
    {
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Yii::debug($query->createCommand()->rawSql, 'SQL_BEFORE');

        $query->andFilterWhere([
            'id' => $this->id,
            'area_m2' => $this->area_m2,
            'required_access_level' => $this->required_access_level,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'cs.location', $this->location]);

        // Yii::debug($query->createCommand()->rawSql, 'SQL_AFTER');


        return $dataProvider;
    }

}