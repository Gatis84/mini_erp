<?php 

namespace console\controllers;

use yii\console\Controller;
use common\models\User;
use Yii;

class UserController extends Controller
{
    public function actionCreateAdmin()
    {
        $user = new User();
        $user->username = 'sys_admin';
        $user->email = 'sys_admin@elva_test.lv';
        $user->status = User::STATUS_ACTIVE;
        $user->setPassword('admin123');
        $user->generateAuthKey();
        $user->generateEmailVerificationToken();
        $user->created_at =date('Y-m-d H:i:s');
        $user->updated_at = date('Y-m-d H:i:s');
        $user->password_reset_token = uniqid(); 
        $user->save(false);

        /*
        // Create corresponding Employee record
        $employee = new Employee();
        $employee->user_id = $user->id;
        $employee->first_name = 'Elva_sys_admin';
        $employee->last_name = 'PHP_Yii2';
        $employee->birth_date = (new \DateTime('1984-07-15'))->format('Y-m-d H:i:s');
        $employee->access_level = 1;
        $employee->role = 'sysAdmin';
        // $employee->status = 10;
        $employee->created_at = (new \DateTime())->format('Y-m-d H:i:s');
        $employee->updated_at = (new \DateTime())->format('Y-m-d H:i:s');
        $employee->save(false);
        */

        $auth = Yii::$app->authManager;
        $sysAdminRole = $auth->getRole('sysAdmin');
        $auth->assign($sysAdminRole, $user->id);

        echo "Admin user '{$user->username}' created with RBAC sysAdmin permissions!\n";
    }
}
