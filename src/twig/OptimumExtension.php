<?php

namespace matfish\Optimum\twig;

use Carbon\Carbon;
use Craft;
use matfish\Optimum\Plugin;
use matfish\Optimum\records\Experiment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use yii\web\Cookie;

class OptimumExtension extends AbstractExtension
{
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

        if (!$e->isActive()) {
            return '';
        }

        $variantName = $this->getVariant($experiment);

        $variant = $e->getVariants()->where("handle='$variantName'")->one();

        $closure = Plugin::getInstance()?->getSettings()?->fireEvent;

        return $closure($e, $variant);
    }
}