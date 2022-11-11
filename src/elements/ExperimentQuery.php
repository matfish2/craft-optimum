<?php

namespace matfish\Optimum\elements;

use craft\elements\db\ElementQuery;

class ExperimentQuery extends ElementQuery
{
    protected function beforePrepare(): bool
    {
        // join in the tablecloth table
        $this->joinElementTable('optimum_experiments');

        $this->query->select([
            'optimum_experiments.id',
            'optimum_experiments.name',
            'optimum_experiments.handle',
            'optimum_experiments.enabled',
            'optimum_experiments.startAt',
            'optimum_experiments.endAt'
        ]);

        return parent::beforePrepare();
    }
}