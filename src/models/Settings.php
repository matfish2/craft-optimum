<?php namespace matfish\Optimum\models;

use matfish\Optimum\records\Experiment;
use matfish\Optimum\records\Variant;
use craft\base\Model;


class Settings extends Model
{
    public ?\Closure $fireEvent = null;
    public string $trackingPlatform = 'ga4';
}
