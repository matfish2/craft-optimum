<?php

namespace matfish\Optimum\services\trackingcode;

use matfish\Optimum\Plugin as Optimum;
use matfish\Optimum\records\Experiment;
use matfish\Optimum\records\Variant;

class TrackingCodeRetriever
{
    /**
     * Retrieve the JavaScript tracking code.
     *
     * @param Experiment $experiment
     * @param Variant $variant
     * @return string
     */
    public function getTrackingCode(Experiment $experiment, Variant $variant): string
    {
        $settings = Optimum::getInstance()->getSettings();

        if ($settings->fireEvent !== null) {
            return ($settings->fireEvent)($experiment, $variant);
        }

        $trackingPlatform = $settings->trackingPlatform;

        switch ($trackingPlatform) {
            case 'ga4':
                $trackingCode = new GA4TrackingCode();
                break;
            case 'mixpanel':
                $trackingCode = new MixpanelTrackingCode();
                break;
            default:
                throw new \InvalidArgumentException("Unsupported tracking platform: $trackingPlatform. Use the `fireEvent` config setting to specify a custom function.");
        }

        return $trackingCode->generate($experiment, $variant);
    }
}
