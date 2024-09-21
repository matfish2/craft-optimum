<?php

namespace matfish\Optimum\services\trackingcode;

use matfish\Optimum\records\Experiment;
use matfish\Optimum\records\Variant;

class GA4TrackingCode implements TrackingCode
{
    /**
     * Generate the JavaScript tracking event code for GA4 using a Custom Dimension.
     *
     * @param Experiment $experiment
     * @param Variant $variant
     * @return string
     */
    public function generate(Experiment $experiment, Variant $variant): string
    {
        return <<<EOD
        gtag('event','$experiment->handle', {'$experiment->handle':'$variant->name'});
        EOD;
    }
}
