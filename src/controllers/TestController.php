<?php

namespace matfish\Optimum\controllers;

use Craft;
use craft\web\Controller;
use yii\web\Cookie;
use Carbon\Carbon;
use matfish\Optimum\twig\OptimumExtension;

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
            case 'segmentation':
                $res = [];
                $experiment = $this->request->getQueryParam('experiment', 'test');
               
                for ($i = 0; $i < 100; $i++) {
                    // Clear the cookie before each iteration
                    Craft::$app->response->cookies->remove("optimum_{$experiment}");
                    
                    $e = new OptimumExtension();
                    $res[] = $e->getVariant($experiment);
                }

                // count the number of times each variant was returned
                $counts = array_count_values($res);
                
                // Clear the cookie one final time to prevent it from affecting the next test run
                Craft::$app->response->cookies->remove("optimum_{$experiment}");
                
                return $this->asJson($counts);
                
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