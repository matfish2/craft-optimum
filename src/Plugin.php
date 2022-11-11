<?php

namespace matfish\Optimum;

use craft\base\Element;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\SetElementTableAttributeHtmlEvent;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use craft\web\UrlManager;
use matfish\Optimum\elements\Experiment;
use matfish\Optimum\models\Settings;
use craft\base\Plugin as BasePlugin;
use Craft;
use matfish\Optimum\twig\OptimumExtension;
use yii\base\Event;

class Plugin extends BasePlugin
{
    public bool $hasCpSection = true;
    public bool $hasCpSettings = true;

    public function init()
    {
        parent::init();

        $this->registerTwigExtension();
//        $this->_registerCpRoutes();
        $this->registerEditRoutes();

        // Modify index table display values
//Event::on(
//    Experiment::class,
//    Element::EVENT_SET_TABLE_ATTRIBUTE_HTML,
//    static function(SetElementTableAttributeHtmlEvent $event) {
//
//        /** @var Experiment $experiment */
////        $entry = $event->sender;
//$event->html = 'Cool';
//        switch ($event->attribute) {
//            case 'computedColumn': // How to identify a computed column
//                $event->html = 'any valid HTML';
//                break;
//            case 'field:101': // How to identify a normal field
//                $event->html = 'whatever you want it to be';
//                break;
//        }
//
//    }
//);
//        Craft::$app->view->hook('cp.elements.element', function (array &$context) {
//            if ($context['context'] === 'index' && $context['viewMode'] === 'table' && $context['tableName'] === 'Experiments') {
//                return 'here babe';
//            }
//
//        });

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
     * Register CP routes.
     */
//    private function _registerCpRoutes(): void
//    {
//        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, static function (RegisterUrlRulesEvent $event): void {
//            $rules = [
//                'settings/optimum' => 'optimum/settings/index',
//                'settings/optimum/actions' => 'optimum/actions/index',
//                'settings/optimum/settings' => 'optimum/settings/settings',
//            ];
//
//            $event->rules = array_merge($event->rules, $rules);
//        });
//    }

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

//    public function registerElementType(): void
//    {
//        Event::on(Elements::class,
//            Elements::EVENT_REGISTER_ELEMENT_TYPES,
//            function (RegisterComponentTypesEvent $event) {
//                $event->types[] = Experiment::class;
//            }
//        );
//    }
}
