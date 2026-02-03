<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use common\models\Employee;
use common\behaviors\DateTimeBehavior;

/**
 * This is the model class for table "task".
 *
 * @property int $id
 * @property int $construction_site_id
 * @property string $title
 * @property string|null $description
 * @property int|null $status
 * @property int $created_by
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $planned_start_at
 * @property string|null $planned_end_at
 * @property string|null $completed_at
 *
 * @property ConstructionSite $constructionSite
 * @property TaskAssignment[] $taskAssignments
 */
class Task extends \yii\db\ActiveRecord
{
    /**
     * Task status describes the lifecycle of the task itself,
     * while assignment status reflects each employee’s progress.
     */

    const STATUS_DRAFT = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_CANCELLED = 2;
    const STATUS_ARCHIVED = 3;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['description'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 0],
            [['updated_at'], 'default', 'value' =>  function() { return date('Y-m-d H:i:s'); }],
            [['construction_site_id', 'title'], 'required'],
            [['construction_site_id', 'status', 'created_by'], 'integer'],
            [['description'], 'string'],
            [['created_at', 'updated_at', 'planned_start_at', 'planned_end_at', 'completed_at'], 'safe'],
            [['title'], 'string', 'max' => 128],
            [['construction_site_id'], 'exist', 'skipOnError' => true, 'targetClass' => ConstructionSite::class, 'targetAttribute' => ['construction_site_id' => 'id']],
             // Custom rule - end date must be after start date
            [['planned_end_at'], 'validateEndAfterStart'],
            
        ];
    }

    public function validateEndAfterStart($attribute, $params)
    {
        if (!$this->hasErrors()) {
            if ($this->planned_end_at && $this->planned_start_at) {
                $start = strtotime($this->planned_start_at);
                $end = strtotime($this->planned_end_at);
                
                if ($end <= $start) {
                    $this->addError($attribute, 'End date/time must be after start date/time.');
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'construction_site_id' => 'Construction Site ID',
            'title' => 'Title',
            'description' => 'Description',
            'status' => 'Status',
            'created_by' => 'Created By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'planned_start_at' => 'Planned Start At',
            'planned_end_at' => 'Planned End At',
            'completed_at' => 'Completed At',
        ];
    }

    /**
     * Gets query for [[ConstructionSite]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getConstructionSite()
    {
        return $this->hasOne(ConstructionSite::class, ['id' => 'construction_site_id']);
    }

    /**
     * Gets query for [[TaskAssignments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAssignments()
    {
        return $this->hasMany(TaskAssignment::class, ['task_id' => 'id']);
    }

    /**
     * Returns list of status labels
     *
     * @return array<int,string>
     */
    public static function statusList(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_ARCHIVED => 'Archived',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function getStatusLabel(): string
    {
        return self::statusList()[$this->status] ?? '—';
    }

    public function getTaskCreator()
    {
        return $this->hasOne(Employee::class, ['user_id' => 'created_by']);
    }

    public function getCreatorData(): ?array
    {
        $e = $this->taskCreator;
        if (!$e) {
            return null;
        }
        return [
            'user_id' => $e->user_id,
            'first_name' => $e->first_name,
            'last_name' => $e->last_name,
            'username' => $e->user->username ?? null,
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => function() {
                    return date('Y-m-d H:i:s'); // MSSQL Server safe format
                },
            ],
            [
            'class' => BlameableBehavior::class,
            'createdByAttribute' => 'created_by',
            'updatedByAttribute' => null,
            ],
            [
            'class' => DateTimeBehavior::class,
                'attributesList' => [
                    'planned_start_at',
                    'planned_end_at',
                    'completed_at',
                    'created_at',
                    'updated_at',
                ],
            ],
        ];
    }

}
