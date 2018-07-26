<?php

namespace ElemeOpenApi\Api;
Vendor('ElemeOpenApi.Api.RpcService');

/**
 * 订单服务
 */
class OrderService extends RpcService
{

    /** 获取订单
     * @param $order_id 订单Id
     * @return mixed
     */
    public function get_order($order_id)
    {
        return $this->client->call("eleme.order.getOrder", array("orderId" => $order_id));
    }

    /** 批量获取订单
     * @param $order_ids 订单Id的列表
     * @return mixed
     */
    public function mget_orders($order_ids)
    {
        return $this->client->call("eleme.order.mgetOrders", array("orderIds" => $order_ids));
    }

    /** 确认订单(推荐)
     * @param $order_id 订单Id
     * @return mixed
     */
    public function confirm_order_lite($order_id)
    {
        return $this->client->call("eleme.order.confirmOrderLite", array("orderId" => $order_id));
    }

    /** 确认订单
     * @param $order_id 订单Id
     * @return mixed
     */
    public function confirm_order($order_id)
    {
        return $this->client->call("eleme.order.confirmOrder", array("orderId" => $order_id));
    }

    /** 取消订单(推荐)
     * @param $order_id 订单Id
     * @param $type 取消原因
     * @param $remark 备注说明
     * @return mixed
     */
    public function cancel_order_lite($order_id, $type, $remark)
    {
        return $this->client->call("eleme.order.cancelOrderLite", array("orderId" => $order_id, "type" => $type, "remark" => $remark));
    }

    /** 取消订单
     * @param $order_id 订单Id
     * @param $type 取消原因
     * @param $remark 备注说明
     * @return mixed
     */
    public function cancel_order($order_id, $type, $remark)
    {
        return $this->client->call("eleme.order.cancelOrder", array("orderId" => $order_id, "type" => $type, "remark" => $remark));
    }

    /** 同意退单/同意取消单(推荐)
     * @param $order_id 订单Id
     * @return mixed
     */
    public function agree_refund_lite($order_id)
    {
        return $this->client->call("eleme.order.agreeRefundLite", array("orderId" => $order_id));
    }

    /** 同意退单/同意取消单
     * @param $order_id 订单Id
     * @return mixed
     */
    public function agree_refund($order_id)
    {
        return $this->client->call("eleme.order.agreeRefund", array("orderId" => $order_id));
    }

    /** 不同意退单/不同意取消单(推荐)
     * @param $order_id 订单Id
     * @param $reason 商家不同意退单原因
     * @return mixed
     */
    public function disagree_refund_lite($order_id, $reason)
    {
        return $this->client->call("eleme.order.disagreeRefundLite", array("orderId" => $order_id, "reason" => $reason));
    }

    /** 不同意退单/不同意取消单
     * @param $order_id 订单Id
     * @param $reason 商家不同意退单原因
     * @return mixed
     */
    public function disagree_refund($order_id, $reason)
    {
        return $this->client->call("eleme.order.disagreeRefund", array("orderId" => $order_id, "reason" => $reason));
    }

    /** 获取订单配送记录
     * @param $order_id 订单Id
     * @return mixed
     */
    public function get_delivery_state_record($order_id)
    {
        return $this->client->call("eleme.order.getDeliveryStateRecord", array("orderId" => $order_id));
    }

    /** 批量获取订单最新配送记录
     * @param $order_ids 订单Id列表
     * @return mixed
     */
    public function batch_get_delivery_states($order_ids)
    {
        return $this->client->call("eleme.order.batchGetDeliveryStates", array("orderIds" => $order_ids));
    }

    /** 配送异常或者物流拒单后选择自行配送(推荐)
     * @param $order_id 订单Id
     * @return mixed
     */
    public function delivery_by_self_lite($order_id)
    {
        return $this->client->call("eleme.order.deliveryBySelfLite", array("orderId" => $order_id));
    }

    /** 配送异常或者物流拒单后选择自行配送
     * @param $order_id 订单Id
     * @return mixed
     */
    public function delivery_by_self($order_id)
    {
        return $this->client->call("eleme.order.deliveryBySelf", array("orderId" => $order_id));
    }

    /** 配送异常或者物流拒单后选择不再配送(推荐)
     * @param $order_id 订单Id
     * @return mixed
     */
    public function no_more_delivery_lite($order_id)
    {
        return $this->client->call("eleme.order.noMoreDeliveryLite", array("orderId" => $order_id));
    }

    /** 配送异常或者物流拒单后选择不再配送
     * @param $order_id 订单Id
     * @return mixed
     */
    public function no_more_delivery($order_id)
    {
        return $this->client->call("eleme.order.noMoreDelivery", array("orderId" => $order_id));
    }

    /** 订单确认送达(推荐)
     * @param $order_id 订单ID
     * @return mixed
     */
    public function received_order_lite($order_id)
    {
        return $this->client->call("eleme.order.receivedOrderLite", array("orderId" => $order_id));
    }

    /** 订单确认送达
     * @param $order_id 订单ID
     * @return mixed
     */
    public function received_order($order_id)
    {
        return $this->client->call("eleme.order.receivedOrder", array("orderId" => $order_id));
    }

    /** 回复催单
     * @param $remind_id 催单Id
     * @param $type 回复类别
     * @param $content 回复内容,如果type为custom,content必填,回复内容不能超过30个字符
     * @return mixed
     */
    public function reply_reminder($remind_id, $type, $content)
    {
        return $this->client->call("eleme.order.replyReminder", array("remindId" => $remind_id, "type" => $type, "content" => $content));
    }

    /** 获取指定订单菜品活动价格.
     * @param $order_id 订单Id
     * @return mixed
     */
    public function get_commodities($order_id)
    {
        return $this->client->call("eleme.order.getCommodities", array("orderId" => $order_id));
    }

    /** 批量获取订单菜品活动价格
     * @param $order_ids 订单Id列表
     * @return mixed
     */
    public function mget_commodities($order_ids)
    {
        return $this->client->call("eleme.order.mgetCommodities", array("orderIds" => $order_ids));
    }

    /** 获取订单退款信息
     * @param $order_id 订单Id
     * @return mixed
     */
    public function get_refund_order($order_id)
    {
        return $this->client->call("eleme.order.getRefundOrder", array("orderId" => $order_id));
    }

    /** 批量获取订单退款信息
     * @param $order_ids 订单Id列表
     * @return mixed
     */
    public function mget_refund_orders($order_ids)
    {
        return $this->client->call("eleme.order.mgetRefundOrders", array("orderIds" => $order_ids));
    }

    /** 取消呼叫配送
     * @param $order_id 订单Id
     * @return mixed
     */
    public function cancel_delivery($order_id)
    {
        return $this->client->call("eleme.order.cancelDelivery", array("orderId" => $order_id));
    }

    /** 呼叫配送
     * @param $order_id 订单Id
     * @param $fee 小费,1-8之间的整数
     * @return mixed
     */
    public function call_delivery($order_id, $fee)
    {
        return $this->client->call("eleme.order.callDelivery", array("orderId" => $order_id, "fee" => $fee));
    }

    /** 获取店铺未回复的催单
     * @param $shop_id 店铺id
     * @return mixed
     */
    public function get_unreply_reminders($shop_id)
    {
        return $this->client->call("eleme.order.getUnreplyReminders", array("shopId" => $shop_id));
    }

    /** 查询店铺未处理订单
     * @param $shop_id 店铺id
     * @return mixed
     */
    public function get_unprocess_orders($shop_id)
    {
        return $this->client->call("eleme.order.getUnprocessOrders", array("shopId" => $shop_id));
    }

    /** 查询店铺未处理的取消单
     * @param $shop_id 店铺id
     * @return mixed
     */
    public function get_cancel_orders($shop_id)
    {
        return $this->client->call("eleme.order.getCancelOrders", array("shopId" => $shop_id));
    }

    /** 查询店铺未处理的退单
     * @param $shop_id 店铺id
     * @return mixed
     */
    public function get_refund_orders($shop_id)
    {
        return $this->client->call("eleme.order.getRefundOrders", array("shopId" => $shop_id));
    }

    /** 查询全部订单
     * @param $shop_id 店铺id
     * @param $page_no 页码。取值范围:大于零的整数最大限制为100
     * @param $page_size 每页获取条数。最小值1，最大值50。
     * @param $date 日期,默认当天,格式:yyyy-MM-dd
     * @return mixed
     */
    public function get_all_orders($shop_id, $page_no, $page_size, $date)
    {
        return $this->client->call("eleme.order.getAllOrders", array("shopId" => $shop_id, "pageNo" => $page_no, "pageSize" => $page_size, "date" => $date));
    }

}