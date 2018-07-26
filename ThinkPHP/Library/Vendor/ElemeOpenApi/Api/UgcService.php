<?php

namespace ElemeOpenApi\Api;

/**
 * 订单评论服务
 */
class UgcService extends RpcService
{

    /** open_a_p_i 查询近2周的评论
     * @param $shop_id 店铺Id
     * @param $offset 分页偏移
     * @param $limit 单页数据
     * @return mixed
     */
    public function query_order_comments($shop_id, $offset, $limit)
    {
        return $this->client->call("eleme.ugc.queryOrderComments", array("shopId" => $shop_id, "offset" => $offset, "limit" => $limit));
    }

    /** open_a_p_i 查询近2周的评论数量
     * @param $shop_id 店铺Id
     * @return mixed
     */
    public function count_order_comments($shop_id)
    {
        return $this->client->call("eleme.ugc.countOrderComments", array("shopId" => $shop_id));
    }

    /** open_a_p_i 回复评论接口
     * @param $shop_id 店铺Id
     * @param $comment_id 评论id
     * @param $content 回复内容
     * @param $replier_name 回复人
     * @return mixed
     */
    public function reply_order_comment($shop_id, $comment_id, $content, $replier_name)
    {
        return $this->client->call("eleme.ugc.replyOrderComment", array("shopId" => $shop_id, "commentId" => $comment_id, "content" => $content, "replierName" => $replier_name));
    }

}