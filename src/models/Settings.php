<?php namespace matfish\Optimum\models;

use craft\base\Model;

class Settings extends Model
{
    public bool $recordSitePageRequests = true;
    public bool $recordSiteAjaxRequests = true;

    public bool $recordCpPageRequests = true;
    public bool $recordCpAjaxRequests = false;

    public bool $recordOnlyActions = false;
    public ?\Closure $requestFilter = null;

    public function rules() : array
    {
        return [
            [['recordSitePageRequests','recordSiteAjaxRequests','recordCpPageRequests','recordCpAjaxRequests','recordOnlyActions'], 'boolean'],
        ];
    }
}
