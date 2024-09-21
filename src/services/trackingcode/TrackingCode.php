<?php

namespace matfish\Optimum\services\trackingcode;


use matfish\Optimum\records\Experiment;
use matfish\Optimum\records\Variant;

interface TrackingCode
{
    /**
     * Generate the JavaScript tracking event code.
     *
     * @param Experiment $experiment
     * @param Variant $variant
     * @return string
     */
    public function generate(Experiment $experiment, Variant $variant): string;
}

