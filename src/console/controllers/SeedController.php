<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;
use common\models\User;
use yii\console\ExitCode;
use common\components\RbacHelper;
use common\models\Task;

class SeedController extends Controller
{
    /**
     * Options available to actions
     */
    public function options($actionID)
    {
        return ['count', 'truncate'];
    }

    public function optionAliases()
    {
        return ['c' => 'count', 't' => 'truncate'];
    }

    /**
     * Seed employee table with fake data.
     *
     * Usage:
     *   php yii seed/employee 100 --truncate=1
     *
     * @param int $count Number of records to generate
     * @param int $truncate Whether to truncate the table before seeding (0/1)
     */
    public function actionEmployee($count = 50, $truncate = 0)
    {
        $count = (int) $count;
        $truncate = (int) $truncate;

        $db = Yii::$app->db;
        $tableName = 'employee';
        $RBACRoles =  ['admin', 'teamLead', 'employee']; //sysAdmin excluded from seeding

        if ($truncate) {
            $this->stdout("Truncating table $tableName\n", Console::FG_YELLOW);
            $this->safeTruncateTable($tableName);
            $this->safeTruncateTable('user');

        }

        // Faker will be loaded via composer dependency
        if (!class_exists(\Faker\Factory::class)) {
            $this->stdout("Faker is not installed. Run: composer require fakerphp/faker\n", Console::FG_RED);
            return ExitCode::UNAVAILABLE;
        }

        $faker = \Faker\Factory::create();

        $this->stdout("Seeding $count employees into $tableName...\n", Console::FG_GREEN);
        Console::startProgress(0, $count);

        for ($i = 0; $i < $count; $i++) {

            $firstName = $faker->firstName;
            // $firstName = $faker->firstName . $i;
            $lastName = $faker->lastName;
            // $lastName = $faker->lastName . rand(1,99);
            $birthDate = $faker->date('Y-m-d', '-18 years');
            $access_level = $faker->numberBetween(1, 5);
            $role = $faker->randomElement($RBACRoles);
            $status = $faker->randomElement([User::STATUS_ACTIVE, User::STATUS_INACTIVE]); // 10/9/0 => From src\common\models\User.php
            $created_at =  $faker->dateTimeBetween('-2 years', 'now');
            $updated_at =  $faker->dateTimeBetween($created_at, 'now');
            // $email = $faker->unique()->safeEmail;
            // $position = $faker->jobTitle;
            // $salary = $faker->numberBetween(30000, 150000);
            // $hiredAt = $faker->dateTimeBetween('-5 years', 'now')->getTimestamp();

            try {

                $user = new User();
                $user->username = strtolower($firstName . '.' . $lastName . '-' . ($i+1));
                $user->email = strtolower($firstName . '.' . $lastName . '-' . ($i+1) . '@elva-test.com');
                $user->status = $status;
                $user->setPassword('user123' . ($i+1));
                $user->generateAuthKey();
                $user->generateEmailVerificationToken();
                $user->created_at = time();
                $user->updated_at = time();
                $user->password_reset_token = uniqid();

                $user->save(false);

                if (!$user->save(false)) {
                    echo "FAILED creating user for $firstName $lastName : " . json_encode($user->errors) .  "\n";
                    continue;
                }

                if (!$user->id) {
                    echo "User save failed for $firstName $lastName\n";
                    continue;
                }

                $db->createCommand()->insert($tableName, [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'birth_date' => $birthDate,
                    'access_level' => $access_level,
                    'role' => $role,
                    // 'status' => $status,
                    'user_id' => $user->id,
                    'created_at' => $created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $updated_at->format('Y-m-d H:i:s'),
                ])->execute();

                // Auto Assign role
                $auth = Yii::$app->authManager;
                $userRole = $auth->getRole($role);
                $auth->assign($userRole, $user->id);
                echo "User '{$user->username}' created with RBAC role {$role}!\n";

            } catch (\Throwable $e) {
                echo "ERROR $i: " . $e->getMessage() . "\n";
                continue;
            }

            Console::updateProgress($i + 1, $count);
        }

        Console::endProgress();
        $this->stdout("\nDone.\n", Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Seed construction_site table
     * Usage: php yii seed/site 10 --truncate=1
     */
    public function actionSite($count = 10, $truncate = 0)
    {
        $count = (int)$count;
        $truncate = (int)$truncate;

        $db = Yii::$app->db;
        $tableName = 'construction_site';

        $role = 'teamLead';
        $activeStatus = User::STATUS_ACTIVE;
        $teamLeadIds = $db->createCommand('
            SELECT e.id FROM {{%employee}} e
            INNER JOIN {{%user}} u ON e.user_id = u.id
            WHERE e.role = :role AND u.status = :status
        ')->bindParam(':role', $role)->bindParam(':status', $activeStatus)->queryColumn();
    

        if (empty($teamLeadIds)) {
            $this->stdout("No teamLeads found. Seed employees first.\n", Console::FG_RED);
            return ExitCode::NOINPUT;
        }

        if ($truncate) {
            $this->stdout("Truncating table $tableName\n", Console::FG_YELLOW);
            $this->safeTruncateTable($tableName);
            $this->safeTruncateTable('construction_assignment');
        }

        if (!class_exists(\Faker\Factory::class)) {
            $this->stdout("Faker is not installed. Run: composer require fakerphp/faker\n", Console::FG_RED);
            return ExitCode::UNAVAILABLE;
        }

        $faker = \Faker\Factory::create();

        $this->stdout("Seeding $count construction sites into $tableName...\n", Console::FG_GREEN);
        Console::startProgress(0, $count);

        for ($i = 0; $i < $count; $i++) {
            $location = $faker->address;
            $area = $faker->numberBetween(200, 20000);
            $requiredAccess = $faker->numberBetween(1, 5);
            $created_at =  $faker->dateTimeBetween('-2 years', 'now');
            $updated_at =  $faker->dateTimeBetween($created_at, 'now');

            try {
                $db->createCommand()->insert($tableName, [
                    'location' => $location,
                    'area_m2' => $area,
                    'required_access_level' => $requiredAccess,
                    'created_at' => $created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $updated_at->format('Y-m-d H:i:s'),
                    
                ])->execute();

                $siteId = $db->getLastInsertID();

                $db->createCommand()->insert('{{%construction_assignment}}', [
                    'construction_site_id' => $siteId,
                    'employee_id' => $faker->randomElement($teamLeadIds),
                    'assigned_at' => $faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d H:i:s'),
                    'completed_at' => $faker->boolean(40)
                        ? $faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d H:i:s')
                        : null,
                ])->execute();
                
            } catch (\Exception $e) {
                $this->stdout("ERROR: {$e->getMessage()}\n", Console::FG_RED);
            }

            Console::updateProgress($i + 1, $count);
        }

        Console::endProgress();
        $this->stdout("\nDone.\n", Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Seed task table
     * Usage: php yii seed/task 100 --truncate=1
     */
    public function actionTask($count = 100, $truncate = 0)
    {
        $count = (int)$count;
        $truncate = (int)$truncate;

        $db = Yii::$app->db;
        $tableName = 'task';

        if ($truncate) {
            $this->stdout("Truncating table $tableName\n", Console::FG_YELLOW);
            $this->safeTruncateTable($tableName);
        }

        if (!class_exists(\Faker\Factory::class)) {
            $this->stdout("Faker is not installed. Run: composer require fakerphp/faker\n", Console::FG_RED);
            return ExitCode::UNAVAILABLE;
        }


        // START Get employee IDs who can create tasks
        $userIds = RbacHelper::userIdsByPermission('task.create');

        $taskCreators = (new \yii\db\Query())
            ->select('id')
            ->from('{{%employee}}')
            ->where(['user_id' => $userIds])
            ->column();

        if (empty($taskCreators)) {
            $this->stdout("No task creators found\n", Console::FG_RED);
            return ExitCode::NOINPUT;
        }
        // END

        $taskIds = $db->createCommand('SELECT id FROM {{%task}}')->queryColumn();
        $employeeIds = $db->createCommand('SELECT id FROM {{%employee}}')->queryColumn();
        $siteIds = $db->createCommand('SELECT id FROM {{%construction_site}}')->queryColumn();
        $taskStatuses = Task::statusList();

        if (empty($employeeIds) || empty($siteIds)) {
            $this->stdout("No employees or construction sites found. Seed those first.\n", Console::FG_RED);
            return ExitCode::NOINPUT;
        }

        $faker = \Faker\Factory::create();

        $this->stdout("Seeding $count tasks into $tableName...\n", Console::FG_GREEN);
        Console::startProgress(0, $count);

        for ($i = 0; $i < $count; $i++) {
            $employeeId = $faker->randomElement($employeeIds);
            $siteId = $faker->randomElement($siteIds);
            // $taskDate = $faker->dateTimeBetween('-1 years', 'now')->format('Y-m-d');
            $description = $faker->sentence(10);
            $title = $faker->sentence(3);
            $status = $faker->randomElement($taskStatuses);
            $created_at =  $faker->dateTimeBetween('-2 years', 'now');
            $updated_at =  $faker->dateTimeBetween($created_at, 'now');

            try {
                $db->createCommand()->insert($tableName, [
                    'construction_site_id' => $siteId,
                    'title' => $title,
                    'description' => $description,
                    // 'employee_id' => $employeeId,
                    // 'task_date' => $taskDate,
                    'status' => $status,
                    'created_by' => $faker->randomElement($taskCreators),
                    'created_at' => $created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $updated_at->format('Y-m-d H:i:s'),
                ])->execute();

                $taskId = $db->getLastInsertID();

                $db->createCommand()->insert('{{%task_assignment}}', [
                    'task_id' => $taskId,
                    'employee_id' => $employeeId,
                    'assigned_at' => $faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d H:i:s'),
                    'completed_at' => $faker->boolean(40)
                        ? $faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d H:i:s')
                        : null,
                    'planned_start_at' => $faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d H:i:s'),
                    'planned_end_at' => $faker->dateTimeBetween('now', '+1 month')->format('Y-m-d H:i:s'),
                    'status' => $faker->randomElement([0,1,2]),
                ])->execute();

            } catch (\Exception $e) {
                // skip on error
            }

            Console::updateProgress($i + 1, $count);
        }

        Console::endProgress();
        $this->stdout("\nDone.\n", Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Truncate a table safely: try TRUNCATE, fall back to DELETE + reseed identity (SQL Server)
     */
    protected function safeTruncateTable($tableName)
    {
        $db = Yii::$app->db;
        try {
            $db->createCommand()->truncateTable($tableName)->execute();
        } catch (\Throwable $e) {
            try {
                $db->createCommand()->delete($tableName)->execute();
            } catch (\Throwable $e2) {
                $this->stdout("Failed to truncate or delete $tableName: " . $e2->getMessage() . "\n", Console::FG_RED);
                return false;
            }

            try {
                $tbl = $db->quoteTableName($tableName);
                // reset to 1, because user and employee id records should start with 1
                $db->createCommand("DBCC CHECKIDENT ($tbl, RESEED, 1)")->execute(); 
            } catch (\Throwable $e3) {
                $this->stdout("Reseed failed for $tableName: " . $e3->getMessage() . "\n", Console::FG_YELLOW);
            }
        }

        return true;
    }
}
