<?php namespace matfish\Optimum\controllers;

use Craft;
use craft\errors\ElementNotFoundException;
use craft\helpers\DateTimeHelper;
use craft\web\View;
use DateInterval;
use DateTime;
use matfish\Optimum\elements\Experiment;
use matfish\Optimum\elements\Variant;
use matfish\Optimum\records\Experiment as ExperimentRecord;
use yii\base\Exception;
use yii\base\InvalidConfigException;

class ExperimentsController extends \craft\web\Controller
{
    protected ?Experiment $experiment;

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

        if ($experiment->isNew()) {
            $variants = [];
        } else {
            $exp = ExperimentRecord::findOne($experiment->id);
            if (!$exp) {
                $variants = [];
            } else {
                $variants = $exp->getVariants()->all();
            }

        }

        $data = [
            'experiment' => $experiment,
            'variants' => $variants,
            'defaultEndAt' => (new DateTime())->add(new DateInterval('P30D'))
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
//        $this->requirePermission('edit-experiment');

        $experimentId = $this->request->getBodyParam('experimentId');

        $this->experiment = $experimentId ? Experiment::findOne($experimentId) : new Experiment();

        if ($experimentId && !$this->experiment) {
            $this->setFailFlash(Craft::t('optimum', 'Experiment id not found'));
            return null;
        }
        $dateErrors = $this->validateDates((bool)$experimentId);

        if (count($dateErrors) > 0) {
            $this->setFailFlash(Craft::t('optimum', 'Invalid date(s)'));

            foreach ($dateErrors as $key => $error) {
                $this->experiment->addError($key, $error);
            }

            Craft::$app->urlManager->setRouteParams([
                'experiment' => $this->experiment
            ]);

            return null;
        }

        $this->setParamsFromRequest();

        // Try to save it
        if (!Craft::$app->elements->saveElement($this->experiment)) {
            $this->setFailFlash(Craft::t('optimum', 'Failed to save experiment'));

            // Send the event back to the edit action
            Craft::$app->urlManager->setRouteParams([
                'experiment' => $this->experiment
            ]);

            return null;
        }

        $varErrors = $this->saveVariants($this->request->getBodyParam('variants'));

        if (count($varErrors) > 0) {
            $this->setFailFlash(Craft::t('optimum', 'Failed to save experiment variants'));


            foreach ($varErrors as $error) {
                $this->experiment->addError('variants', $error);
            }

            // If new experiment delete experiment
            if (!$experimentId) {
                ExperimentRecord::findOne($this->experiment->id)->delete();
                $this->experiment->id = null;
            }

            // Send the event back to the edit action
            Craft::$app->urlManager->setRouteParams([
                'experiment' => $this->experiment,
                'variants' => $this->request->getBodyParam('variants')
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
    private function setParamsFromRequest()
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
                    $value = DateTimeHelper::toDateTime($value)->format('Y-m-d H:i:s');
                } else {
                    $value = null;
                }
            }

            $this->experiment->{$key} = $value;
        }
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

    private function saveVariants(array $variants): array
    {
        $newIds = [];
        $errors = [];
        $totalWeight = 0;

        foreach ($variants as $index => $variant) {

            $v = new Variant();
            $v->experimentId = $this->experiment->id;
            $v->name = $variant['name'];
            $v->handle = $variant['handle'];
            $v->weight = (int)$variant['weight'];

            $totalWeight += $v->weight;

            if (!$v->name) {
                $errors[] = "Name must be set on variant {$index}";
            }

            if (!$v->handle) {
                $errors[] = "Handle must be set on variant {$index}";
            }

            if (!$v->weight) {
                $errors[] = "Weight must be set on variant {$index}";
            }

            if ($v->weight < 0 || $v->weight > 100) {
                $errors[] = "Weight must be a number between 0 and 100 on variant {$index}";
            }

            if (!Craft::$app->elements->saveElement($v)) {
                $errors[] = "Failed to save variant {$index}";
            }

            $newIds[] = $v->id;
        }

        if ($totalWeight !== 100) {
            $errors[] = "Total variants weight must be 100%";
        }

        $newIds = implode(",", $newIds);

        \matfish\Optimum\records\Variant::deleteAll("experimentId={$this->experiment->id} AND id NOT IN ({$newIds})");

        return $errors;
    }

    private function validateDates($isEdit): array
    {
        $values = Craft::$app->request->getBodyParams();
        $errors = [];

        foreach (['startAt', 'endAt'] as $key) {
            $value = $values[$key];

            if (!$value['date']) {
                continue;
            }

            $dates[$key] = DateTimeHelper::toDateTime($value);

            if (!$dates[$key]) {
                $errors[$key] = "Invalid date time format";
            }
        }

        if (isset($dates['startAt'], $dates['endAt']) && $dates['startAt'] > $dates['endAt']) {
            $errors['startAt'] = 'Start date cannot be later than end date';
        }

        if (!$isEdit && isset($dates['endAt']) && $dates['endAt'] < new DateTime()) {
            $errors['endAt'] = 'End date must be greater than now';
        }

        return $errors;
    }


}