<?php namespace matfish\Optimum\models;

use craft\base\Model;

class Settings extends Model
{
    public ?\Closure $fireEvent = null;
    public string $trackingPlatform = 'ga4';
    public function rules(): array
    {
        return [
            ['trackingPlatform', 'string'],
            ['fireEvent', 'safe'],
        ];
    }
}
