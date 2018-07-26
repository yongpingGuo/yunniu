<?php

namespace ElemeOpenApi\Api;

/**
 * 消息服务
 */
class MessageService extends RpcService
{

    /** 获取未到达的推送消息
     * @param $app_id 应用ID
     * @return mixed
     */
    public function get_non_reached_messages($app_id)
    {
        return $this->client->call("eleme.message.getNonReachedMessages", array("appId" => $app_id));
    }

    /** 获取未到达的推送消息实体
     * @param $app_id 应用ID
     * @return mixed
     */
    public function get_non_reached_o_messages($app_id)
    {
        return $this->client->call("eleme.message.getNonReachedOMessages", array("appId" => $app_id));
    }

}