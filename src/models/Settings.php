<?php namespace matfish\Optimum\models;

use craft\base\Model;

class Settings extends Model
{
    public ?\Closure $fireEvent = null;

    public function __construct()
    {
        parent::__construct();
        $this->fireEvent = static function ($experiment, $variant) {
            return <<<EOD
gtag('event','$experiment->handle', {'$experiment->handle':'$variant->name'});
EOD;
        };
    }
}
