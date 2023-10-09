<?php

namespace matfish\Optimum;

use craft\base\Element;
use craft\db\ActiveRecord;
use craft\db\Table;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\web\UrlManager;
use matfish\Optimum\models\Settings;
use craft\base\Plugin as BasePlugin;
use Craft;
use matfish\Optimum\elements\Experiment;
use matfish\Optimum\records\Experiment as ExperimentRecord;
use matfish\Optimum\twig\OptimumExtension;
use yii\base\Event;
use yii\db\BaseActiveRecord;

class Plugin extends BasePlugin
{
    public bool $hasCpSection = true;
    public bool $hasCpSettings = false;

    public function init()
    {
        parent::init();

        $this->registerTwigExtension();
        $this->registerEditRoutes();
        $this->registerAfterDeleteEvent();

        if (Craft::$app->request->isCpRequest) {
            $this->controllerNamespace = 'matfish\\Optimum\\controllers';
        } elseif (Craft::$app->request->isConsoleRequest) {
            $this->controllerNamespace = 'matfish\\Optimum\\controllers\\console';
        }

    }

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    protected function settingsHtml(): null|string
    {
        return \Craft::$app->getView()->renderTemplate(
            'optimum/settings',
            ['settings' => $this->getSettings()]
        );
    }

    /**
     * Edit routes
     */
    public function registerEditRoutes(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            static function (RegisterUrlRulesEvent $event) {
                $event->rules['optimum/experiments/new'] = 'optimum/experiments/edit';
                $event->rules['optimum/experiments/<experimentId:\d+>'] = 'optimum/experiments/edit';
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse(): mixed
    {
        $url = UrlHelper::cpUrl('settings/optimum');

        Craft::$app->controller->redirect($url);

        return '';
    }

    private function registerTwigExtension(): void
    {
        Craft::$app->view->registerTwigExtension(new OptimumExtension());
    }

    /**
     * @return array
     */
    public function getCpNavItem(): array
    {
        $item = parent::getCpNavItem();
        $item['label'] = 'Experiments';

        return $item;
    }

    private function registerAfterDeleteEvent(): void
    {
        Event::on(
            Experiment::class,
            Element::EVENT_AFTER_DELETE,
            static function ($event) {
                $element = $event->sender;

                Db::delete('{{%optimum_experiments}}', [
                    'id' => $element->id,
                ]);

                Db::delete('{{%optimum_experiment_variants}}', [
                    'experimentId' => $element->id,
                ]);
            }
        );
    }
}
