<?php

	include_once("../WxPayPubHelper/WxPayPubHelper.php");

	//使用统一支付接口
	$unifiedOrder = new UnifiedOrder_pub();

	$unifiedOrder->setParameter("body","贡献一分钱打死都不干");//商品描述
	//自定义订单号，此处仅作举例
	$timeStamp = time();
	$out_trade_no = WxPayConf_pub::APPID."$timeStamp";
	$unifiedOrder->setParameter("out_trade_no","$out_trade_no");//商户订单号 
	$unifiedOrder->setParameter("total_fee","1");//总金额
	$unifiedOrder->setParameter("notify_url",WxPayConf_pub::NOTIFY_URL);//通知地址 
	$unifiedOrder->setParameter("trade_type","NATIVE");
	//非必填参数，商户可根据实际情况选填
	$unifiedOrder->setParameter("sub_mch_id","1412338802");//注：是主户代理申请的 这里的子商户的商户号

	
	//获取统一支付接口结果
	$unifiedOrderResult = $unifiedOrder->getResult();
	
	//商户根据实际情况设置相应的处理流程
	if ($unifiedOrderResult["return_code"] == "FAIL") 
	{
		//商户自行增加处理流程
		echo "通信出错：".$unifiedOrderResult['return_msg']."<br>";
	}
	elseif($unifiedOrderResult["result_code"] == "FAIL")
	{
		//商户自行增加处理流程
		echo "错误代码：".$unifiedOrderResult['err_code']."<br>";
		echo "错误代码描述：".$unifiedOrderResult['err_code_des']."<br>";
	}
	elseif($unifiedOrderResult["code_url"] != NULL)
	{
		//从统一支付接口获取到code_url
		$code_url = $unifiedOrderResult["code_url"];
		//商户自行增加处理流程
		//......
	}

	echo $code_url;