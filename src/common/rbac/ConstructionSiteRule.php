<?php

namespace common\rbac;

use yii\rbac\Rule;
use common\models\ConstructionAssignment;
class ConstructionSiteRule extends Rule
{
    public $name = 'canManageConstructionSite';

    public function execute($userId, $item, $params)
    {
        if (empty($params['constructionSiteId'])) {
            return false;
        }

        return ConstructionAssignment::find()
            ->joinWith('employee')
            ->where([
                'construction_assignment.construction_site_id' => $params['constructionSiteId'],
                'employee.user_id' => $userId,
            ])
            ->exists();
    }
}
