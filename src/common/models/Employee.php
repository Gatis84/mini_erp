<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use common\components\RbacHelper;
use yii\helpers\ArrayHelper;


/**
 * This is the model class for table "employee".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $first_name
 * @property string $last_name
 * @property string $birth_date
 * @property int $access_level
 * @property string $role
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property TaskAssignment[] $taskAssignments
 * @property User $user
 */

//* @property int|null $status

class Employee extends ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'employee';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'default', 'value' => null],
            [['updated_at'], 'default', 'value' => function() { return date('Y-m-d H:i:s'); }],
            [['user_id', 'access_level'], 'integer'],
            [['first_name', 'last_name', 'birth_date', 'access_level', 'role'], 'required'],
            [['birth_date', 'created_at', 'updated_at'], 'safe'],
            [['first_name', 'last_name'], 'string', 'max' => 100],
            [['role'], 'string', 'max' => 50],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'birth_date' => 'Birth Date',
            'access_level' => 'Access Level',
            'role' => 'Role',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[TaskAssignments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTaskAssignments()
    {
        return $this->hasMany(TaskAssignment::class, ['employee_id' => 'id']);
    }

    public function getConstructionAssignments()
    {
        return $this->hasMany(ConstructionAssignment::class, [
            'employee_id' => 'id'
        ]);
    }

    public function getConstructionSites()
    {
        return $this->hasMany(ConstructionSite::class, ['id' => 'construction_site_id'])
            ->via('constructionAssignments');
    }

    public function getEmployeesByRole($role)
    {
        return self::find()
            ->where(['role' => $role])
            ->all();
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
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
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->updated_at = date('Y-m-d H:i:s');
            if ($insert && !$this->created_at) {
                $this->created_at = date('Y-m-d H:i:s');
            }
            return true;
        }
        return false;
    }

    public static function gettaskCreators()
    {
        return self::find()
            ->select('user_id')
            ->where(['user_id' => RbacHelper::userIdsByPermission('task.create')]);
            // ->andWhere(['status' => User::STATUS_ACTIVE]);
    }

    public static function getRoleList()
    {
        $auth = \Yii::$app->authManager;
        $roles = $auth->getRoles();
        
        $roleList = [];
        foreach ($roles as $name => $role) {
            if ($name !== 'sysAdmin') {  // Exclude sysAdmin
                $roleList[$name] = $name;
            }
        }
        return $roleList;
    }

}
