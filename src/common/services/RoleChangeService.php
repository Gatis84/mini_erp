<?php

namespace common\services;

use Yii;
use yii\db\Expression;
use yii\db\Transaction;

class RoleChangeService
{
    /**
     * Permission → cleanup handler map
     */
    private static array $permissionCleanupMap = [

        // if no more permissions for construction site edit
        'project.update' => [
            'table' => '{{%construction_assignment}}',
            'column' => 'employee_id',
        ],

        /*
        // if no more permissions for ALL task edit
        'task.create' => [
            'table' => '{{%task_assignment}}',
            'column' => 'employee_id',
        ],

        // if no more permissions for limited task edit
        'task.createLimited' => [
            'table' => '{{%task_assignment}}',
            'column' => 'employee_id',
        ],*/
    ];

    /**
     * Sync domain data after role change
     */
    public static function syncAfterRoleChange(int $userId, array $oldPermissions, array $newPermissions): void 
    {
        $revokedPermissions = array_diff($oldPermissions, $newPermissions);

        if (empty($revokedPermissions)) {
            return;
        }

        $db = Yii::$app->db;
        Yii::info("RoleChangeService: userId={$userId}, revokedPermissions=" . implode(',', $revokedPermissions), __METHOD__);
        $tx = $db->beginTransaction(Transaction::SERIALIZABLE);

        try {
            foreach ($revokedPermissions as $permission) {
                Yii::info("RoleChangeService: processing permission {$permission}", __METHOD__);

                if (!isset(self::$permissionCleanupMap[$permission])) {
                    Yii::info("RoleChangeService: no cleanup mapping for {$permission}", __METHOD__);
                    continue;
                }

                $config = self::$permissionCleanupMap[$permission];

                // Build condition depending on target column type. If the target column is an employee_id
                // we need to map from user id -> employee id(s) because constructions/tasks use employee.id, not user.id
                $condition = [$config['column'] => $userId];

                if ($config['column'] === 'employee_id') {
                    $employeeIds = (new \yii\db\Query())
                        ->select('id')
                        ->from('{{%employee}}')
                        ->where(['user_id' => $userId])
                        ->column();

                    if (empty($employeeIds)) {
                        // nothing to update for this user
                        Yii::info("RoleChangeService: no employee records found for user {$userId}, skipping {$permission}", __METHOD__);
                        continue;
                    }

                    $condition = [$config['column'] => $employeeIds];
                }

                $affected = $db->createCommand()
                    ->update(
                        $config['table'],
                        [
                            'active' => 0,
                            'ended_at' => new Expression('GETDATE()')
                        ],
                        $condition
                    )
                    ->execute();

                Yii::info("RoleChangeService: Updated {$affected} rows on {$config['table']} where " . json_encode($condition), __METHOD__);
            }

            $tx->commit();
        } catch (\Throwable $e) {
            Yii::error($e->getMessage());
            $tx->rollBack();
            throw $e;
        }
    }


}
