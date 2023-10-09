<?php

namespace matfish\Optimum\elements;

use Carbon\Carbon;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\UrlHelper;
use matfish\Optimum\actions\DeleteAction;
use matfish\Optimum\records\Experiment as ExperimentRecord;

class Experiment extends Element
{

    public bool $hardDelete = true;

    public ?string $name = null;
    public ?string $handle = null;
    public bool $enabled = true;
    public $startAt = null;
    public $endAt = null;

    public function rules(): array
    {
        return [
            [['name', 'handle', 'endAt'], 'required'],
            [['handle'],
                'unique',
                'targetClass' => ExperimentRecord::class,
                'filter' => function ($query) {
                    if ($this->id !== null) {
                        $query->andWhere('`id` != :id', ['id' => $this->id]);
                    }
                }
            ],
            [['name'],
                'unique',
                'targetClass' => ExperimentRecord::class,
                'filter' => function ($query) {
                    if ($this->id !== null) {
                        $query->andWhere('`id` != :id', ['id' => $this->id]);
                    }
                }
            ],
            [['handle'], 'match', 'pattern' => '/^[a-zA-Z0-9_]+$/']
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
                return "<div><span class='status $enabled element'></span></div>";
            }
            case 'startAt':
                $this[$attribute] = $this[$attribute] ?? $this->dateCreated;
                return Carbon::parse($this[$attribute])->toDayDateTimeString();
            case 'endAt':
            {
                return Carbon::parse($this[$attribute])->toDayDateTimeString();
            }
            case 'duration':
            {
                $start = $this->startAt = $this->startAt ?? $this->dateCreated;
                return Carbon::parse($this->endAt)->diffInDays($start) . ' days';
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
        return ['enabled', 'startAt', 'endAt', 'duration'];
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
            'enabled' => \Craft::t('app', 'Enabled?'),
            'startAt' => \Craft::t('app', 'Start date'),
            'endAt' => \Craft::t('app', 'End date'),
            'duration' => \Craft::t('app', 'Duration')
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