<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Employee;
use Yii;

/**
 * EmployeeSearch represents the model behind the search form of `common\models\Employee`.
 */
class EmployeeSearch extends Employee
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // [['id', 'user_id', 'access_level', 'status'], 'integer'],
            [['id', 'user_id', 'access_level'], 'integer'],
            [['first_name', 'last_name', 'birth_date', 'role', 'created_at', 'updated_at'], 'safe'],
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
        $query = Employee::find()->alias('e');

        // BACKEND SYSADMIN — NO FILTERS
        if (Yii::$app->id === 'app-backend' && Yii::$app->user->can('sysAdmin')) {
            return $this->buildProvider($query, $params, $formName);
        }

        /**
         * FRONTEND admin — redz visus
         */
        if (Yii::$app->user->can('project.create')) {
            return $this->buildProvider($query, $params, $formName);
        }

        /**
         * FRONTEND teamLead
         * Redz TIKAI darbiniekus, kas strādā viņa pārvaldītajos objektos
         */
        if (Yii::$app->user->can('project.update')) {

            $teamLead = Employee::findOne(['user_id' => Yii::$app->user->id]);

            if (!$teamLead) {
                $query->andWhere('0=1'); // nav darbinieka ieraksta — neredz neko, drošības pēc atgriež tukšu sarakstu
                return $this->buildProvider($query, $params, $formName);
            }

            $subQuery = (new \yii\db\Query())
                ->select('ta.employee_id')
                ->from(['ca' => 'construction_assignment'])
                ->innerJoin(['t' => 'task'], 't.construction_site_id = ca.construction_site_id')
                ->innerJoin(['ta' => 'task_assignment'], 'ta.task_id = t.id')
                ->where(['ca.employee_id' => $teamLead->id]);

            $query->andWhere(['e.id' => $subQuery]);

            return $this->buildProvider($query, $params, $formName);
        }

        /**
         * EMPLOYEE — redz tikai sevi
         */

        // echo '<pre>';
        // var_dump(Yii::$app->user->can('employee.viewOwn'));die;
        // if (Yii::$app->user->can('employee.viewOwn')) {
            $query->andWhere(['e.user_id' => Yii::$app->user->id]);
            return $this->buildProvider($query, $params, $formName);
        // }

        // drošības fallback
        // $query->andWhere('0=1');
        // return $this->buildProvider($query, $params, $formName);

    }

    protected function buildProvider($query, $params, $formName)
    {
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params, $formName);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'e.id' => $this->id,
            'e.user_id' => $this->user_id,
            'e.birth_date' => $this->birth_date,
            'e.access_level' => $this->access_level,
            // 'e.status' => $this->status,
            'e.created_at' => $this->created_at,
            'e.updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'e.first_name', $this->first_name])
            ->andFilterWhere(['like', 'e.last_name', $this->last_name])
            ->andFilterWhere(['like', 'e.role', $this->role]);

        return $dataProvider;
    }

}
