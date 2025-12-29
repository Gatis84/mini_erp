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
            [['id', 'user_id', 'access_level', 'status'], 'integer'],
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

        // FRONTEND ADMIN — NO FILTERS
        if (Yii::$app->id === 'app-frontend' && Yii::$app->user->can('admin')) {
            return $this->buildProvider($query, $params, $formName);
        }

        // BACKEND SYSADMIN — NO FILTERS
        if (Yii::$app->id === 'app-backend' && Yii::$app->user->can('sysAdmin')) {
            return $this->buildProvider($query, $params, $formName);
        }

        // EMPLOYEE – can view only their own record
        if (Yii::$app->user->can('employee') 
            && !Yii::$app->user->can('teamLead') )
        {
            $query->andWhere(['employee.user_id' => Yii::$app->user->id]);
        }

        // TEAM LEAD FE - can view employees withaccess level <= their own
        if (Yii::$app->user->can('teamLead')) {

            $employee = Employee::findOne(['user_id' => Yii::$app->user->id]);

            $subQuery = (new \yii\db\Query())
                ->select('employee.id')
                ->from('employee')
                ->leftJoin('task_assignment', 'employee.id = task_assignment.employee_id')
                ->leftJoin('task', 'task_assignment.task_id = task.id')
                ->leftJoin('construction_site', 'task.construction_site_id = construction_site.id')
                ->where(['<=', 'construction_site.required_access_level', $employee->access_level])
                ->groupBy('employee.id');

            $query->andWhere(['employee.id' => $subQuery]);
        }


        return $this->buildProvider($query, $params, $formName);
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
            'employee.id' => $this->id,
            'employee.user_id' => $this->user_id,
            'birth_date' => $this->birth_date,
            'access_level' => $this->access_level,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'first_name', $this->first_name])
            ->andFilterWhere(['like', 'last_name', $this->last_name])
            ->andFilterWhere(['like', 'role', $this->role]);

        return $dataProvider;
    }

}
