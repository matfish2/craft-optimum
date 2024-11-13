<?php

namespace matfish\Optimum\migrations;

use Craft;
use craft\db\Migration;

/**
 * m241113_104350_add_population_segment migration.
 */
class m241113_104350_add_population_segment extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%optimum_experiments}}', 'populationSegment')) {
            $this->addColumn('{{%optimum_experiments}}', 'populationSegment', $this->integer()->notNull()->defaultValue(100)->after('enabled'));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m241113_104350_add_population_segment cannot be reverted.\n";
        return false;
    }
}
