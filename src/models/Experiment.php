<?php


namespace matfish\ActivityLog\models;

use craft\base\Model;

class Experiment extends Model
{
    public ?int $userId;
    public ?float $execTime;
    public string $url;
    public string $method;
    public ?string $query;
    public ?string $payload;
    public string $ip;
    public string $userAgent;
    public bool $isAjax;
    public int $siteId;
    public bool $isCp;
    public bool $isAction;
    public ?string $actionSegments;
}