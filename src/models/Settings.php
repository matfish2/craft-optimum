<?php namespace matfish\Optimum\models;

use matfish\Optimum\records\Experiment;
use matfish\Optimum\records\Variant;
use craft\base\Model;


class Settings extends Model
{
    public ?\Closure $fireEvent = null;

    public function __construct()
    {
        parent::__construct();
        $this->fireEvent = static function (Experiment $experiment, Variant $variant) {
            return <<<EOD
gtag('event','$experiment->handle', {'$experiment->handle':'$variant->name'});
EOD;
        };
    }
}
