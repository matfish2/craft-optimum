<?php

namespace matfish\Optimum\twig;

use Carbon\Carbon;
use Craft;
use matfish\Optimum\Plugin;
use matfish\Optimum\records\Experiment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use yii\web\Cookie;
use matfish\Optimum\services\trackingcode\TrackingCodeRetriever;

class OptimumExtension extends AbstractExtension
{
    protected string $variant;

    public function getTokenParsers(): array
    {
        return [
            new OptimumTokenParser()
        ];
    }

    public function getName(): string
    {
        return 'optimum';
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('optimumGetVariant', [$this, 'getVariant']),
            new TwigFunction('optimumFireEvent', [$this, 'fireEvent'])
        ];
    }

    public function getVariant(string $experiment): string
    {
        $e = Experiment::find()->where("handle='$experiment'")->one();

        if (!$e) {
            throw new \Exception("Optimum: Unknown experiment {$experiment}");
        }

        return $e->getOrSetExperimentCookie();
    }

    public function fireEvent(string $experiment): string
    {
        $e = Experiment::find()->where("handle='$experiment'")->one();

        if (!$e) {
            throw new \Exception("Optimum: Unknown experiment {$experiment}");
        }

        $variantName = $this->getVariant($experiment);

        $variant = $e->getVariants()->where("handle='$variantName'")->one();

        return (new TrackingCodeRetriever())->getTrackingCode($e, $variant);
    }

    /**
     * @throws \Exception
     */
    protected function getRandomWeightedElement(array $weightedValues): string
    {
        $rand = random_int(1, (int)array_sum($weightedValues));

        foreach ($weightedValues as $key => $value) {
            $rand -= $value;
            if ($rand <= 0) {
                return $key;
            }
        }

        throw new \Exception("Optimum: Failed to randomize element");
    }

    function getOrSetExperimentCookie($experiment): string
    {
        $vars = [];

        $variants = $experiment->getVariants()->all();

        foreach ($variants as $variant) {
            $vars[$variant->handle] = $variant->weight;
        }
        $testVar = Craft::$app->request->getParam('optimum');

        if ($testVar && array_key_exists($testVar, $vars)) {
            return $testVar;
        }

        $key = "optimum_{$experiment->handle}";
        $cookie = \Craft::$app->request->cookies->get($key);

        if ($cookie) {
            return $cookie->value;
        }

        $randomVariant = $this->getRandomWeightedElement($vars);

        // Create cookie object.
        $cookie = Craft::createObject([
            'class' => Cookie::class,
            'name' => $key,
            'httpOnly' => true,
            'value' => $randomVariant,
            'expire' => Carbon::parse($experiment->endAt)->unix(),
        ]);

        Craft::$app->getResponse()->getCookies()->add($cookie);

        return $randomVariant;
    }
}