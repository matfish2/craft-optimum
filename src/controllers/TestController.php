<?php

namespace matfish\Optimum\controllers;

use Craft;
use craft\web\Controller;
use yii\web\Cookie;
use Carbon\Carbon;

class TestController extends Controller
{
    public function actionIndex()
    {
        $scenario = Craft::$app->request->getQueryParam('scenario', 'default');
        $experiment = Craft::$app->request->getQueryParam('experiment', 'test');
        $variant = Craft::$app->request->getQueryParam('variant', 'copycat1');
        $returnUrl = Craft::$app->request->getQueryParam('return', '/');
        $segment = (int)Craft::$app->request->getQueryParam('segment', 100);

        $key = "optimum_{$experiment}";

        // First, update the experiment's population segment if provided
        if (!is_null($segment)) {
            $e = \matfish\Optimum\records\Experiment::find()
                ->where(['handle' => $experiment])
                ->one();
            
            if ($e) {
                $e->populationSegment = $segment;
                $e->save();
            }
        }

        switch ($scenario) {
            case 'legacy':
                $value = $variant;
                break;
                
            case 'included':
                $value = json_encode([
                    'variant' => $variant,
                    'included' => true
                ]);
                break;
                
            case 'excluded':
                $value = json_encode([
                    'variant' => null,
                    'included' => false
                ]);
                break;
            
            case 'malformed':
                $value = 'not a valid JSON string';
                break;
                
            case 'test-segment':
                // Clear cookie to test fresh segmentation
                Craft::$app->response->cookies->remove($key);
                return $this->redirect($returnUrl);
                
            case 'clear':
                Craft::$app->response->cookies->remove($key);
                return $this->redirect($returnUrl);
                
            default:
                return 'Available scenarios: force-included, force-excluded, test-segment, clear. Optional params: segment (0-100)';
        }

        if (isset($value)) {
            $cookie = Craft::createObject([
                'class' => Cookie::class,
                'name' => $key,
                'httpOnly' => true,
                'value' => $value,
                'expire' => Carbon::now()->addYear()->timestamp,
            ]);

            Craft::$app->response->cookies->add($cookie);
        }

        return $this->redirect($returnUrl);
    }
} 