<?php

namespace PayMethod\Wechat;

use PayMethod\Wechat\Lib\WechatCommon;

/**
 * 微信前端 JavaScript 签名SDK
 * 
 * @author Anyon <zoujingli@qq.com>
 * @date 2016/06/28 11:24
 */
class WechatScript extends WechatCommon {

    private $jsapi_ticket;

    /**
     * 删除JSAPI授权TICKET
     * @param string $appid 用于多个appid时使用
     */
    public function resetJsTicket($appid = '') {
        $this->jsapi_ticket = '';
        $authname = 'wechat_jsapi_ticket_' . empty($appid) ? $this->appid : $appid;
        $this->removeCache($authname);
        return true;
    }

    /**
     * 获取JSAPI授权TICKET
     * @param string $appid 用于多个appid时使用,可空
     * @param string $jsapi_ticket 手动指定jsapi_ticket，非必要情况不建议用
     */
    public function getJsTicket($appid = '', $jsapi_ticket = '') {
        if (!$this->access_token && !$this->checkAuth()) {
            return false;
        }
        if (empty($appid)) {
            $appid = $this->appid;
        }
        # 手动指定token，优先使用
        if ($jsapi_ticket) {
            $this->jsapi_ticket = $jsapi_ticket;
            return $this->jsapi_ticket;
        }
        # 尝试从缓存中读取
        $jt = $this->getCache(($authname = 'wechat_jsapi_ticket_' . $appid));
        if ($jt) {
            return $this->jsapi_ticket = $jt;
        }
        # 调接口获取
        $result = $this->http_get(self::API_URL_PREFIX . self::GET_TICKET_URL . 'access_token=' . $this->access_token . '&type=jsapi');
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            $this->jsapi_ticket = $json['ticket'];
            $this->setCache($authname, $this->jsapi_ticket, $json['expires_in'] ? intval($json['expires_in']) - 100 : 3600);
            return $this->jsapi_ticket;
        }
        return false;
    }

    /**
     * 获取JsApi使用签名
     * @param string $url 网页的URL，自动处理#及其后面部分
     * @param string $timestamp 当前时间戳 (为空则自动生成)
     * @param string $noncestr 随机串 (为空则自动生成)
     * @param string $appid 用于多个appid时使用,可空
     * @return array|bool 返回签名字串
     */
    public function getJsSign($url, $timestamp = 0, $noncestr = '', $appid = '') {
        if (!$this->jsapi_ticket && !$this->getJsTicket($appid) || empty($url)) {
            return false;
        }
        $data = array(
            "jsapi_ticket" => $this->jsapi_ticket,
            "timestamp"    => empty($timestamp) ? time() : $timestamp,
            "noncestr"     => '' . empty($noncestr) ? $this->createNoncestr(16) : $noncestr,
            "url"          => trim($url),
        );
        return array(
            # 'debug' => true,
            "appId"     => empty($appid) ? $this->appid : $appid,
            "nonceStr"  => $data['noncestr'],
            "timestamp" => $data['timestamp'],
            "signature" => $this->getSignature($data, 'sha1'),
            'jsApiList' => array(
                'onMenuShareTimeline', 'onMenuShareAppMessage', 'onMenuShareQQ', 'onMenuShareWeibo', 'onMenuShareQZone',
                'hideOptionMenu', 'showOptionMenu', 'hideMenuItems', 'showMenuItems', 'hideAllNonBaseMenuItem', 'showAllNonBaseMenuItem',
                'chooseImage', 'previewImage', 'uploadImage', 'downloadImage', 'closeWindow', 'scanQRCode', 'chooseWXPay',
                'translateVoice', 'getNetworkType', 'openLocation', 'getLocation',
                'openProductSpecificView', 'addCard', 'chooseCard', 'openCard',
                'startRecord', 'stopRecord', 'onVoiceRecordEnd', 'playVoice', 'pauseVoice', 'stopVoice', 'onVoicePlayEnd', 'uploadVoice', 'downloadVoice',
            )
        );
    }

}
