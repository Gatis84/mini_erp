<?php 

namespace common\models;

use yii\db\ActiveRecord;

class ConstructionAssignment extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%construction_assignment}}';
    }

    public function rules()
    {
        return [
            [['construction_site_id', 'employee_id'], 'required'],
            [['construction_site_id', 'employee_id'], 'integer'],
            [['construction_site_id', 'employee_id'], 'unique',
                'targetAttribute' => ['construction_site_id', 'employee_id'],
            ],
        ];
    }

    public function getConstructionSite()
    {
        return $this->hasOne(ConstructionSite::class, ['id' => 'construction_site_id']);
    }

    public function getEmployee()
    {
        return $this->hasOne(Employee::class, ['id' => 'employee_id']);
    }

    public function getAssignmentsByEmployee($employee_id): array
    {
        return self::find()
            // ->with('employee')
            ->with('constructionSite')
            ->where(['employee_id' => (int)$employee_id])
            ->orderBy(['assigned_at' => SORT_DESC, 'id' => SORT_DESC])
            ->all();
    }
}
