<?php

namespace ElemeOpenApi\Api;

/**
 * 金融服务
 */
class FinanceService extends RpcService
{

    /** 查询商户余额,返回可用余额和总余额
     * @param $shop_id 饿了么店铺Id
     * @return mixed
     */
    public function query_balance($shop_id)
    {
        return $this->client->call("eleme.finance.queryBalance", array("shopId" => $shop_id));
    }

    /** 查询余额流水,有流水改动的交易
     * @param $request 查询条件
     * @return mixed
     */
    public function query_balance_log($request)
    {
        return $this->client->call("eleme.finance.queryBalanceLog", array("request" => $request));
    }

}