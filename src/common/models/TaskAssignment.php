<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "task_assignment".
 *
 * @property int $id
 * @property int $task_id
 * @property int $employee_id
 * @property string|null $assigned_at
 * @property string|null $completed_at
 * @property string|null $planned_start_at
 * @property string|null $planned_end_at
 * @property int|null $status
 *
 * @property Employee $employee
 * @property Task $task
 */
class TaskAssignment extends \yii\db\ActiveRecord
{

    const STATUS_ASSIGNED = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_OVERDUE = 3;

    public $employee_ids;


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task_assignment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['employee_ids'], 'required', 'on' => ['create', 'update']],
            [['employee_ids'], 'each', 'rule' => ['integer']],
            [['completed_at'], 'default', 'value' => null],
            // [['assigned_at'], 'default', 'value' => function() { return date('Y-m-d H:i:s'); } ],
            [['status'], 'default', 'value' => 0],
            [['task_id', 'employee_id'], 'required'],
            [['task_id', 'employee_id', 'status'], 'integer'],
            [['assigned_at', 'completed_at'], 'safe'],
            [['planned_start_at', 'planned_end_at'], 'safe'],
            [['employee_id'], 'exist', 'skipOnError' => true, 'targetClass' => Employee::class, 'targetAttribute' => ['employee_id' => 'id']],
            [['task_id'], 'exist', 'skipOnError' => true, 'targetClass' => Task::class, 'targetAttribute' => ['task_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'task_id' => 'Task ID',
            'employee_id' => 'Employee ID',
            'assigned_at' => 'Assigned At',
            'planned_start_at' => 'Planned Start At',
            'planned_end_at' => 'Planned End At',
            'completed_at' => 'Completed At',
            'status' => 'Status',
        ];
    }

    /**
     * Gets query for [[Employee]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEmployee()
    {
        return $this->hasOne(Employee::class, ['id' => 'employee_id']);
    }

    /**
     * Gets query for [[Task]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTask()
    {
        return $this->hasOne(Task::class, ['id' => 'task_id']);
    }

    public static function statusList(): array
    {
        return [
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_OVERDUE => 'Overdue',
        ];
    }

    public function getStatusLabel(): string
    {
        return self::statusList()[$this->status] ?? '—';
    }

}
