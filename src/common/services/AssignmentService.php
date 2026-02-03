<?php

namespace common\services;

use common\models\TaskAssignment;
use common\models\User;
use Yii;
use yii\db\Expression;

class AssignmentService
{
    /**
     * Deactivate construction and task assignments for the given user.
     */
    public static function deactivateAssignmentsForUser(int $userId): void
    {
        $db = Yii::$app->db;

        $employeeIds = (new \yii\db\Query())
            ->select('id')
            ->from('{{%employee}}')
            ->where(['user_id' => $userId])
            ->column();

        if (empty($employeeIds)) {
            Yii::info("AssignmentService: deactivateAssignmentsForUser: no employee records for user {$userId}", __METHOD__);
            return;
        }

        $tx = $db->beginTransaction();
        try {
            $affected = $db->createCommand()
                ->update(
                    '{{%construction_assignment}}',
                    [
                        'active' => 0,
                        'ended_at' => new Expression('GETDATE()')
                    ],
                    ['employee_id' => $employeeIds]
                )
                ->execute();

            Yii::info("AssignmentService: deactivateAssignmentsForUser: Updated {$affected} rows in construction_assignment for user {$userId}", __METHOD__);

            try {
                $userStatus = (new \yii\db\Query())
                    ->select('status')
                    ->from('{{%user}}')
                    ->where(['id' => $userId])
                    ->scalar();

                // change employee`s assigned tasks status to CANCELED just if he is deleted or inactive
                if (in_array((int)$userStatus, [User::STATUS_INACTIVE, User::STATUS_DELETED], true)) {
                    $db->createCommand()
                        ->update(
                            '{{%task_assignment}}',
                            [
                                'status' => TaskAssignment::STATUS_CANCELED,
                            ],
                            ['and', ['employee_id' => $employeeIds], ['<>', 'status', TaskAssignment::STATUS_CANCELED]]
                        )
                        ->execute();

                    Yii::info("AssignmentService: deactivateAssignmentsForUser: Updated task_assignment status to canceled for user {$userId}", __METHOD__);
                } else {
                    Yii::info("AssignmentService: deactivateAssignmentsForUser: user {$userId} status is not inactive/deleted, task assignments left unchanged", __METHOD__);
                }
            } catch (\Throwable $e) {
                Yii::info('AssignmentService: deactivateAssignmentsForUser: task_assignment update skipped: ' . $e->getMessage(), __METHOD__);
            }

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error($e->getMessage());
            throw $e;
        }
    }

    /**
     * Reactivate previously deactivated construction assignments for the given user.
     */
    public static function reactivateAssignmentsForUser(int $userId): void
    {
        $db = Yii::$app->db;

        $employeeIds = (new \yii\db\Query())
            ->select('id')
            ->from('{{%employee}}')
            ->where(['user_id' => $userId])
            ->column();

        if (empty($employeeIds)) {
            Yii::info("AssignmentService: reactivateAssignmentsForUser: no employee records for user {$userId}", __METHOD__);
            return;
        }

        $tx = $db->beginTransaction();
        try {
            $affected = $db->createCommand()
                ->update(
                    '{{%construction_assignment}}',
                    [
                        'active' => 1,
                        'reassigned_at' => new Expression('GETDATE()'),
                        'ended_at' => null,
                    ],
                    ['employee_id' => $employeeIds, 'active' => 0]
                )
                ->execute();

            Yii::info("AssignmentService: reactivateAssignmentsForUser: Updated {$affected} rows in construction_assignment for user {$userId}", __METHOD__);

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error($e->getMessage());
            throw $e;
        }
    }
}