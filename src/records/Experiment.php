<?php

namespace matfish\Optimum\records;


class Experiment extends \craft\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%optimum_experiments}}';
    }

    public function getVariants(): \craft\db\ActiveQuery
    {
        return $this->hasMany(Variant::class, ['experimentId' => 'id']);
    }
}