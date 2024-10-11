<?php

namespace matfish\Optimum\services\trackingcode;

use matfish\Optimum\records\Experiment;
use matfish\Optimum\records\Variant;

class MixpanelTrackingCode implements TrackingCode
{
    /**
     * Generate the JavaScript tracking event code for Mixpanel.
     *
     * @param Experiment $experiment
     * @param Variant $variant
     * @return string
     */
    public function generate(Experiment $experiment, Variant $variant): string
    {
        return <<<EOD
mixpanel.track('\$experiment_started', {
    'Experiment name': '$experiment->handle',
    'Variant name': '$variant->name'
});
EOD;
    }
}

