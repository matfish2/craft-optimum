<?php

namespace matfish\Optimum\records;

class Variant extends \craft\db\ActiveRecord
{
   public static function tableName()
    {
        return '{{%optimum_experiment_variants}}';
    }
}