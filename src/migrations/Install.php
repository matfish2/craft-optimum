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
                'title' => $this->string()->notNull(),
                'slug' => $this->string()->notNull(), // = GA4 custom dimension name (e.g cta_button)
                'section'=>$this->string()->notNull(), // = GA4 custom dimension param (e.g color)
                'startAt' => $this->dateTime()->null(),
                'endAt' => $this->dateTime()->null(),
                'isActive' => $this->boolean(),
                'dateCreated' => $this->timestamp(),
                'dateUpdated' => $this->timestamp()
            ]);
        }

        if (!$this->db->tableExists(self::VARIANTS)) {
            $this->createTable(self::VARIANTS, [
                'id' => $this->primaryKey()->notNull(),
                'experimentId'=>$this->bigInteger()->unsigned()->notNull(),
                'name' => $this->string()->notNull(),
                'weight'=>$this->smallInteger()->notNull(),
                'dateCreated' => $this->timestamp(),
                'dateUpdated' => $this->timestamp()
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