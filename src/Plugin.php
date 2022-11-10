<?php

namespace matfish\Optimum;

use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\web\UrlManager;
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

//        if (Craft::$app->request->isCpRequest) {
//            $this->controllerNamespace = 'matfish\\ActivityLog\\controllers';
//        } elseif (Craft::$app->request->isConsoleRequest) {
//            $this->controllerNamespace = 'matfish\\ActivityLog\\controllers\\console';
//        }
//
//        if (!$this->db->tableExists('{{%activitylog}}')) {
//            return;
//        }
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
     * Register CP routes.
     */
    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event): void {
            $rules = [
                'settings/activity-logs' => 'activity-logs/settings/index',
                'settings/activity-logs/actions' => 'activity-logs/actions/index',
                'settings/activity-logs/settings' => 'activity-logs/settings/settings',
            ];

            $event->rules = array_merge($event->rules, $rules);
        });
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse(): mixed
    {
        $url = UrlHelper::cpUrl('settings/activity-logs');

        Craft::$app->controller->redirect($url);

        return '';
    }

    private function registerTwigExtension(): void
    {
        Craft::$app->view->registerTwigExtension(new OptimumExtension());
    }
}
