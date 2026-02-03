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
 * @property int|null $status
 * @property Employee $employee
 * @property Task $task
 */
class TaskAssignment extends \yii\db\ActiveRecord
{
    /**
     * Task assignment status reflects each employee’s progress
     * while Task status describes the lifecycle of the task itself
     */
    const STATUS_ASSIGNED = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_OVERDUE = 3;
    const STATUS_CANCELED = 4;

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
            [['status'], 'default', 'value' => 0],
            [['task_id', 'employee_id'], 'required'],
            [['task_id', 'employee_id', 'status'], 'integer'],
            [['assigned_at'], 'safe'],
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

    /**
     * Gets all assignments from `task_assignment` for a given employee id.
     * Returns an array of TaskAssignment models (includes all statuses).
     *
     * @param int $employee_id
     * @return TaskAssignment[]
     */
    public function getEmployeeTasks($employee_id): array
    {
        return self::find()
            ->with('task')
            ->where(['employee_id' => (int)$employee_id])
            ->orderBy(['assigned_at' => SORT_DESC, 'id' => SORT_DESC])
            ->all();
    }

    public static function statusList(): array
    {
        return [
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_CANCELED => 'Canceled',
        ];
    }

    public function getStatusLabel(): string
    {
        return self::statusList()[$this->status] ?? '—';
    }

}
