<?php namespace matfish\Optimum\controllers;

use Carbon\Carbon;
use Craft;
use craft\errors\ElementNotFoundException;
use craft\web\View;
use matfish\Optimum\elements\Experiment;
use matfish\Optimum\elements\Variant;
use matfish\Optimum\records\Experiment as ExperimentRecord;
use yii\base\Exception;
use yii\base\InvalidConfigException;

class ExperimentsController extends \craft\web\Controller
{
    protected array $params = [
        'name' => 'string',
        'handle' => 'string',
        'startAt' => 'datetime',
        'endAt' => 'datetime',
        'enabled' => 'boolean'
    ];

    public function actionEdit(): \yii\web\Response
    {
        $experiment = $this->getExperimentForEdit();

        if (!$experiment) {
            return $this->redirect('optimum');
        }

        $data = [
            'experiment' => $experiment,
            'variants' => $experiment->id ? ExperimentRecord::findOne($experiment->id)->getVariants()->all() : [],
            'defaultEndAt' => Carbon::now()->addDays(30)
        ];

        return $this->renderTemplate('optimum/_edit', $data, View::TEMPLATE_MODE_CP);
    }

    /**
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws InvalidConfigException
     * @throws Exception
     * @throws \JsonException
     */
    public function actionSave()
    {
        // Ensure the user has permission to save events
//        $this->requirePermission('edit-tablecloth');

        $experimentId = $this->request->getBodyParam('experimentId');

        $experiment = $experimentId ? Experiment::findOne($experimentId) : new Experiment();

        if (!$experiment) {
            $this->setFailFlash(Craft::t('optimum', 'Experiment id not found'));
            return null;
        }

        $experiment = $this->setParamsFromRequest($experiment);
        // Try to save it
        if (!Craft::$app->elements->saveElement($experiment) || !$this->saveVariants($experiment->id, $this->request->getBodyParam('variants'))) {
            $this->setFailFlash(Craft::t('optimum', 'Failed to save experiment'));
            ExperimentRecord::findOne($experiment->id)->delete();

            // Send the event back to the edit action
            Craft::$app->urlManager->setRouteParams([
                'experiment' => $experiment,
            ]);

            return null;
        }


        $this->setSuccessFlash(Craft::t('optimum', 'Experiment saved.'));

        $this->redirect('optimum');
    }

    /**
     * @throws \JsonException
     * @throws \yii\base\InvalidConfigException
     */
    private function setParamsFromRequest(Experiment $experiment): Experiment
    {
        $values = Craft::$app->request->getBodyParams();

        foreach ($this->params as $key => $type) {
            if (!isset($values[$key])) {
                continue;
            }
            $value = $values[$key];

            if ($type === 'boolean') {
                $value = (bool)$value;
            } elseif ($type === 'integer') {
                $value = (int)$value;
            } elseif ($type === 'datetime') {
                if ($value['date']) {
                    $value = Carbon::parse($value['date'] . ' ' . $value['time']);
                } else {
                    $value = null;
                }
            }

            $experiment->{$key} = $value;
        }

        return $experiment;
    }


    private function getExperimentForEdit(): Experiment
    {
        $routeParams = Craft::$app->urlManager->getRouteParams();

        // failed request, return old model to repopulate form
        if (isset($routeParams['experiment']) && $routeParams['experiment']) {
            return $routeParams['experiment'];
        }

        // edit
        if (isset($routeParams['experimentId']) && $routeParams['experimentId']) {
            return Experiment::findOne($routeParams['experimentId']);
        }

        // create
        return (new Experiment());
    }

    private function saveVariants(int $experimentId, array $variants): bool
    {
        $newIds = [];
        foreach ($variants as $variant) {

            $v = new Variant();
            $v->experimentId = $experimentId;
            $v->name = $variant['name'];
            $v->handle = $variant['handle'];
            $v->weight = $variant['weight'];

            if (!Craft::$app->elements->saveElement($v)) {
                return false;
            }

            $newIds[] = $v->id;
        }

        $newIds = implode(",", $newIds);

        \matfish\Optimum\records\Variant::deleteAll("experimentId=$experimentId AND id NOT IN ({$newIds})");

        return true;
    }


}