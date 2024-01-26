<?php

namespace matfish\Optimum\migrations;

use craft\db\Migration;

class Install extends Migration
{
    const EXPERIMENTS = '{{%optimum_experiments}}';
    const VARIANTS = '{{%optimum_experiment_variants}}';

    public function safeUp()
    {
        if (!$this->db->tableExists(self::EXPERIMENTS)) {
            $this->createTable(self::EXPERIMENTS, [
                'id' => $this->primaryKey()->notNull(),
                'name' => $this->string()->notNull()->unique(),
                'handle' => $this->string()->notNull()->unique(), // = GA4 custom dimension name (e.g cta_button)
                'startAt' => $this->dateTime()->null(),
                'endAt' => $this->dateTime()->null(),
                'enabled' => $this->boolean()->defaultValue(false),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull()
            ]);
        }

        if (!$this->db->tableExists(self::VARIANTS)) {
            $this->createTable(self::VARIANTS, [
                'id' => $this->primaryKey()->notNull(),
                'experimentId' => $this->bigInteger()->unsigned()->notNull(),
                'name' => $this->string()->notNull(), // Sent to GA4,
                'handle' => $this->string()->notNull(), // Used as template name
                'description' => $this->string()->null(),
                'weight' => $this->smallInteger()->notNull(), // in percents
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull()
            ]);
        }
    }

    public function safeDown()
    {
        if ($this->db->tableExists(self::EXPERIMENTS)) {
            $this->dropTable(self::EXPERIMENTS);
        }

        if ($this->db->tableExists(self::VARIANTS)) {
            $this->dropTable(self::VARIANTS);
        }
    }
}