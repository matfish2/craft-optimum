<?php

namespace matfish\Optimum\elements;

use craft\base\Element;

class Variant extends Element
{
    public bool $hardDelete = true;

    public int $experimentId;
    public string $name;
    public string $handle;
    public int $weight;
    public ?string $description = null;

    /**
     * @param bool $isNew
     * @throws \yii\db\Exception
     */
    public function afterSave(bool $isNew): void
    {
        if ($isNew) {
            \Craft::$app->db->createCommand()
                ->insert('{{%optimum_experiment_variants}}', $this->_getInsertData())
                ->execute();
        } else {
            \Craft::$app->db->createCommand()
                ->update('{{%optimum_experiment_variants}}', $this->_getUpdateData(), ['id' => $this->id])
                ->execute();
        }

        parent::afterSave($isNew);
    }

    /**
     * @return array
     */
    private function _getInsertData(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'handle' => $this->handle,
            'weight' => $this->weight,
            'experimentId'=>$this->experimentId,
            'description' => $this->description,
        ];
    }

    /**
     * @return array
     * @throws \JsonException
     */
    private function _getUpdateData(): array
    {
        if ($this->handle === 'original') {
            return [
                'weight' => $this->weight
            ];
        }

        return [
            'name' => $this->name,
            'handle' => $this->handle,
            'experimentId'=>$this->experimentId,
            'weight' => $this->weight,
            'description' => $this->description,
        ];
    }
}