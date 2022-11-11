<?php

namespace matfish\Optimum\elements;

use Carbon\Carbon;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use matfish\Optimum\actions\DeleteAction;

class Experiment extends Element
{

    public ?string $name = null;
    public ?string $handle = null;
    public bool $enabled = true;
    public $startAt = null;
    public $endAt = null;

    public function rules(): array
    {
        return [
            [['name', 'handle', 'endAt'], 'required']
        ];
    }

    /**
     * @return bool
     */
    public function isNew(): bool
    {
        return !$this->id;
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return \Craft::t('optimum', 'Experiment');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return \Craft::t('optimum', 'Experiments');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle(): ?string
    {
        return 'experiment';
    }

    /**
     * @return ElementQueryInterface
     */
    public static function find(): ElementQueryInterface
    {
        return new ExperimentQuery(static::class);
    }

    /**
     * @param bool $isNew
     * @throws \yii\db\Exception
     */
    public function afterSave(bool $isNew): void
    {
        if ($isNew) {
            \Craft::$app->db->createCommand()
                ->insert('{{%optimum_experiments}}', $this->_getInsertData())
                ->execute();
        } else {
            \Craft::$app->db->createCommand()
                ->update('{{%optimum_experiments}}', $this->_getUpdateData(), ['id' => $this->id])
                ->execute();
        }

        parent::afterSave($isNew);
    }

    public function getTableAttributeHtml($attribute): string
    {

        $root = \Craft::getAlias('@web');
        $cpTrigger = getenv('CP_TRIGGER') ?: 'admin';
        $enabled = $this->enabled ? 'enabled' : '';

        switch ($attribute) {
            case 'name':
            {
                return "<span class='status $enabled'></span><a href='$root/$cpTrigger/optimum/experiments/$this->id'>$this->name</a>";
            }
        }

        return $this[$attribute];
    }

    public static function defineDefaultTableAttributes(string $source): array
    {
        return ['name'];
    }

    /**
     * @return array
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'id' => \Craft::t('app', 'ID'),
            'name' => \Craft::t('app', 'Name'),
        ];
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['name', 'handle'];
    }

    protected static function defineActions(string $source = null): array
    {
        return [
            DeleteAction::class,
        ];
    }

    /**
     * @param string|null $context
     * @return array[]
     */
    protected static function defineSources(string $context = null): array
    {
        return [
            [
                'key' => '*',
                'label' => 'All Experiments',
                'criteria' => []
            ],
        ];
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
            'enabled' => $this->enabled,
            'startAt' => $this->startAt,
            'endAt' => $this->endAt
        ];
    }

    /**
     * @return array
     * @throws \JsonException
     */
    private function _getUpdateData(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'handle' => $this->handle,
            'enabled' => $this->enabled,
            'startAt' => $this->startAt,
            'endAt' => $this->endAt
        ];
    }

}