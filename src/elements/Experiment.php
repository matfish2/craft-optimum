<?php

namespace matfish\Optimum\elements;

use Carbon\Carbon;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\UrlHelper;
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

        $enabled = $this->enabled ? 'enabled' : '';

        switch ($attribute) {
            case 'enabled':
            {
                return "<span class='status $enabled element'></span>";
            }
            case 'endAt':
            {
                return Carbon::parse($this[$attribute])->format('d-m-Y H:i');
            }
        }

        return $this[$attribute];
    }

    public function cpEditUrl(): ?string
    {
        $path = sprintf('optimum/experiments/%s', $this->id);
        return UrlHelper::cpUrl($path);
    }

    public function getUiLabel(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl(): ?string
    {
        $cpEditUrl = $this->cpEditUrl();

        if (!$cpEditUrl) {
            return null;
        }

        $params = [];


        return UrlHelper::urlWithParams($cpEditUrl, $params);
    }

    public function canView(User $user): bool
    {
        return true;
    }


    public static function defineDefaultTableAttributes(string $source): array
    {
        return ['id', 'enabled', 'endAt'];
    }

    public function canDelete(User $user): bool
    {
        return true;
    }

    /**
     * @return array
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'id' => \Craft::t('app', 'ID'),
            'enabled' => \Craft::t('app', 'Enabled?'),
            'endAt' => \Craft::t('app', 'Ends At')
//            'name' => \Craft::t('app', 'Name'),
        ];
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['name'];
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