<?php

namespace matfish\ActivityLog\records;


class Experiment extends \craft\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%optimum_experiments}}';
    }
}