<?php

namespace ElemeOpenApi\Api;
Vendor('ElemeOpenApi.Protocol.RpcClient');
use ElemeOpenApi\Config\Config;
use ElemeOpenApi\Protocol\RpcClient;

class RpcService
{
    protected $client;

    public function __construct($token, Config $config)
    {
        $this->client = new RpcClient($token, $config);
    }
}