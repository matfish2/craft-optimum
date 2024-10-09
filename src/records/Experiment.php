<?php

namespace matfish\Optimum\records;


use Carbon\Carbon;
use Craft;

class Experiment extends \craft\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%optimum_experiments}}';
    }

    public function getVariants(): \craft\db\ActiveQuery
    {
        return $this->hasMany(Variant::class, ['experimentId' => 'id']);
    }

    public function isActive(): bool
    {
        return $this->enabled &&
            (!$this->startAt || Carbon::parse($this->startAt) <= Carbon::now()) &&
            (!$this->endAt || Carbon::parse($this->endAt) >= Carbon::now());
    }

    public function getRandomWeightedElement(): string
    {
        $variants = $this->getVariants()->all();
        $vars = [];
        foreach ($variants as $variant) {
            $vars[$variant->handle] = $variant->weight;
        }

        $rand = random_int(1, (int)array_sum($vars));

        foreach ($vars as $key => $value) {
            $rand -= $value;
            if ($rand <= 0) {
                return $key;
            }
        }

        throw new \Exception("Optimum: Failed to randomize element");
    }

    public function getOrSetExperimentCookie(): string
    {
        $testVar = Craft::$app->request->getParam('optimum');

        if ($testVar) {
            $variants = array_map(function ($item) {
                return $item->handle;
            }, $this->getVariants()->all());

            if (in_array($testVar, $variants, true)) {
                return $testVar;
            }
        }

        $key = "optimum_{$this->handle}";

        $cookie = \Craft::$app->request->cookies->get($key);

        if ($cookie) {
            return $cookie->value;
        }

        // if this is the first request,
        // and variant was already set by optimum twig block or fireEvent
        // get cookie from response
        // as it will not be found on request
        $cookie = \Craft::$app->response->cookies->get($key);

        if ($cookie) {
            return $cookie->value;
        }

        $randomVariant = $this->getRandomWeightedElement();

        // Create cookie object.
        $cookie = Craft::createObject([
            'class' => \yii\web\Cookie::class,
            'name' => $key,
            'httpOnly' => true,
            'value' => $randomVariant,
            'expire' => Carbon::parse($this->endAt)->format('U'),
        ]);

        Craft::$app->getResponse()->getCookies()->add($cookie);

        return $randomVariant;
    }
}
