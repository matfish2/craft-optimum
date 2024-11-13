<?php

namespace matfish\Optimum\twig;

use matfish\Optimum\records\Experiment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
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
        if (!$e->isActive() || !$e->isIncludedInExperiment()) {
            return 'original';
        }

        return $e->getOrSetExperimentCookie() ?? 'original';
    }

    public function fireEvent(string $experiment): string
    {
        $e = Experiment::find()->where("handle='$experiment'")->one();

        if (!$e) {
            throw new \Exception("Optimum: Unknown experiment {$experiment}");
        }

        if (!$e->isActive() || !$e->isIncludedInExperiment()) {
            return '';
        }

        $variantName = $this->getVariant($experiment);
        $variant = $e->getVariants()->where("handle='$variantName'")->one();

        return (new TrackingCodeRetriever())->getTrackingCode($e, $variant);
    }
}