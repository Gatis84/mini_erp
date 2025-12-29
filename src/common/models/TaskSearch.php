<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Task;
use Yii;

/**
 * TaskSearch represents the model behind the search form of `common\models\Task`.
 */
class TaskSearch extends Task
{
    /**
     * {@inheritdoc}
     */
    public $employee_id;
    public function rules()
    {

        return [
            [['id', 'construction_site_id', 'status', 'created_by'], 'integer'],
            [['title', 'description', 'created_at', 'updated_at'], 'safe'],
            [['employee_id'], 'integer'],
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
    $query = Task::find()
            ->alias('t')
            ->joinWith(['assignments a', 'assignments.employee e', 'constructionSite cs']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params, $formName);
        if (!$this->validate()) {
            $query->andWhere('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere(['t.status' => $this->status]);
        $query->andFilterWhere([
            't.construction_site_id' => $this->construction_site_id,
        ]);

    /**
     * BACKEND SYSADMIN — redz visu
     */
    if (Yii::$app->id === 'app-backend' && Yii::$app->user->can('sysAdmin')) {
        return $dataProvider;
    }

    /**
     * FRONTEND ADMIN - can see all tasks
     */
    if (Yii::$app->user->can('task.create')) {
        return $dataProvider;
    }

    $employee = Employee::findOne(['user_id' => Yii::$app->user->id]);
    if (!$employee) {
        $query->andWhere('0=1');
        return $dataProvider;
    }

    /**
     * FRONTEND TEAM LEAD
     * tasks where their team is assigned
     * tasks where teamLead is assigned
     */
    if (Yii::$app->user->can('project.view') 
        && !Yii::$app->user->can('task.viewOwn')) {

        // Projects where the team lead has assignments
        $siteSubQuery = (new \yii\db\Query())
            ->select('construction_site_id')
            ->distinct()
            ->from('construction_assignment')
            ->where(['employee_id' => $employee->id]);

        $query->andWhere([
            'OR',
            ['t.construction_site_id' => $siteSubQuery],
            ['e.user_id' => Yii::$app->user->id],
        ]);

        return $dataProvider;
    }

    /**
     * FRONTEND EMPLOYEE — can see only their own tasks
     */
    if (Yii::$app->user->can('task.viewOwn')) {
        $query->andWhere(['e.user_id' => Yii::$app->user->id]);
        return $dataProvider;
    }

    $query->andWhere('0=1');
    return $dataProvider;
}

    protected function buildProvider($query, $params, $formName)
    {
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params, $formName);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'construction_site_id' => $this->construction_site_id,
            // 'task.employee_id' => $this->employee_id,
            'task.status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'description', $this->description]);

        return $dataProvider;
    }
}
