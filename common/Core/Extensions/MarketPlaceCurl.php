<?php

namespace Core\Extensions;

use Core\Http\Curl;
use Core\Extensions\ExtensionsService;
use Core\Core;

defined('ROOT') or exit('Access denied!');

class MarketPlaceCurl extends Curl
{
    protected string $siteID = '';

    protected string $endPoint = 'https://299ko.ovh/';

    protected ExtensionsService $service;

    public function __construct(string $url, ExtensionsService $service)
    {
        $this->service = $service;
        $this->endPoint = Core::getInstance()->getEnv('marketplaceUrl', $this->endPoint);
        parent::__construct($this->endPoint . $url);
        $marketConfig = $this->service->getMarketplaceConfig();
        if (!isset($marketConfig['siteID'])) {
            $marketConfig['siteID'] = uniqid('299ko-', true);
            $this->service->saveMarketplaceConfig($marketConfig);
        }
        $this->siteID = $marketConfig['siteID'];
    }

    public function url(string $url): Curl
    {
        $this->url = $this->endPoint . $url;
        return $this;
    }

    public function setDatas($datas): self
    {
        $this->datas = $datas;
        $this->datas['siteID'] = $this->siteID;
        $this->datas['siteURL'] = Core::getInstance()->getConfigVal('siteUrl');
        $this->datas['siteLang'] = Core::getInstance()->getConfigVal('siteLang');
        $this->datas['siteVersion'] = VERSION;
        return $this;
    }

    public function execute(): Curl
    {
        if (empty($this->datas)) {
            $this->setDatas([]);
        }
        $this->addOption(CURLOPT_USERAGENT, '299ko-curl-marketplace');
        return parent::execute();
    }
}


