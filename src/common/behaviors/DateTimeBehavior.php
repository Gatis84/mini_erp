<?php

namespace common\behaviors;

use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;

class DateTimeBehavior extends AttributeBehavior
{

    public array $attributesList = [];

    public function events(): array
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'convert',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'convert',
        ];
    }

    /**
     * Converts datetime-local(HTML ISO format YYYY-MM-DDTHH:MM used in forms) input 
     * to MSSQL datetime format Y-m-d H:i:s (Yii2 DATETIME / DATETIME2 -> YYYY-MM-DD HH:MM:SS)
     */
    public function convert(): void
    {
        foreach ($this->attributesList as $attribute) {
            $value = $this->owner->{$attribute};

            if ($value === null || $value === '') {
                continue;
            }

            // format ISO from datetime-local input
            $value = str_replace('T', ' ', $value);

            // set to MSSQL datetime format
            $this->owner->{$attribute} = date(
                'Y-m-d H:i:s',
                strtotime($value)
            );
        }
    }
}
