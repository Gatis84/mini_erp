<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use common\models\Task;
use common\models\ConstructionAssignment;

/**
 * This is the model class for table "construction_site".
 *
 * @property int $id
 * @property string $location
 * @property int $area_m2
 * @property int $required_access_level
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Task[] $tasks
 */
class ConstructionSite extends \yii\db\ActiveRecord
{

    public $teamLeadIds = []; // Virtual attribute for form
    public array $teamLeadAssignments = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'construction_site';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['updated_at'], 'default', 'value' => function() { return date('Y-m-d H:i:s'); }],
            [['location', 'area_m2', 'required_access_level'], 'required'],
            [['area_m2', 'required_access_level'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['location'], 'string', 'max' => 255],
            [['teamLeadIds'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'location' => 'Location',
            'area_m2' => 'Area M2',
            'required_access_level' => 'Required Access Level',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Tasks]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTasks()
    {
        return $this->hasMany(Task::class, ['construction_site_id' => 'id']);
    }

    public function getConstructionAssignments()
    {
        return $this->hasMany(ConstructionAssignment::class, [
            'construction_site_id' => 'id'
        ]);
    }

    public function getTeamLeads()
    {
        return $this->hasMany(Employee::class, ['id' => 'employee_id'])
            ->via('constructionAssignments');
    }

    public static function getAccessLevels()
    {
        return [
            1 => 'Level 1',
            2 => 'Level 2',
            3 => 'Level 3',
            4 => 'Level 4',
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
                    return date('Y-m-d H:i:s');
                },
            ],
        ];
    }

}
