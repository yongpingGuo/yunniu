<?php
namespace Api\Controller;
class ErpController extends BaseController
{
		public function SaveSales(){
		$token = $this->getToken();
		$token = 'Bearer '.$token;
		$orderData = I("post.orderData");
		$orderData = str_replace("&quot;","\"",$orderData);
        $orderData = str_replace("&amp;quot;","\"",$orderData);
        $orderDataInfo = json_decode($orderData);

		$data['wyid'] = $orderDataInfo->wyid;
		$data['wdbh'] = $orderDataInfo->wdbh;
		$data['czybh'] = $orderDataInfo->czybh;
		$data['xssj'] = $orderDataInfo->xssj;
		$data['xssj'] = (date('Y/m/d H:i:s', $data['xssj']));
		$data['ssje'] = $orderDataInfo->ssje;
		$date = $orderDataInfo->SalesDetails;
		foreach ($date as $key => $value) {
			$data['SalesDetails'].=$value->cpbh.','.$value->sl.','.$value->dj.','.$value->yj.'|';
		}
		$data['SalesDetails'] = rtrim($data['SalesDetails'], '|');
		// $data['wyid'] = I("post.wyid");
		// $data['wdbh'] = I("post.wdbh");
		// $data['czybh'] = I("post.czybh");
		// $data['xssj'] = I("post.xssj");
		// $data['ysje'] = I("post.ysje");
		// $data['ssje'] = I("post.ssje");
		// $data['SalesDetails'] = I("post.SalesDetails");
 		$header = array();
 		$header[] = 'Authorization:'.$token;
 		// $header[] = 'Accept:application/json';
 		// $header[] = 'Content-Type:application/json;charset=utf-8';
		$url = "http://salesapi999.bakingerp.com:10982/api/Sales/v1/PostSaveSales";
              
              //$list = '[{"store_id":"131","store_number":"90000001","username":"ABC","password":"456789"},{"store_id":"131","store_number":"90000001","username":"guoyongping","password":"1"}]';
              
              //初始化curl
              $curl = curl_init();
              curl_setopt($curl, CURLOPT_URL, $url);
              //设置请求的代理信息
              $user_agent = isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT'] : "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
              curl_setopt($curl,CURLOPT_USERAGENT,$user_agent);
              //设置请求来源信息
              curl_setopt($curl,CURLOPT_AUTOREFERER,TRUE);
              //设置和ssl相关的信息
              curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
              curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
              curl_setopt($curl, CURLOPT_POST, 1);
              curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
              curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);
              curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
              $output = curl_exec($curl);
              curl_close($curl);
              //$arr = json_decode($output,true);
          
		// $returnData['code'] = 1;
  //       $returnData['msg'] = "获取数据成功";
  //       $returnData['data'] = $data;
  //       exit(json_encode($returnData));
	}

	public function getToken(){
		$list['appId'] = '31';
		$list['appSecret'] = '110331661431431801091631414734';
		$url = "http://salesapi999.bakingerp.com:10982/api/TokenAuth/v1/GetAuthToken";
              //$list = json_encode($list);
              //$list = '[{"store_id":"131","store_number":"90000001","username":"ABC","password":"456789"},{"store_id":"131","store_number":"90000001","username":"guoyongping","password":"1"}]';
              //初始化curl
              $curl = curl_init();
              curl_setopt($curl, CURLOPT_URL, $url);
              //设置请求的代理信息
              $user_agent = isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT'] : "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
              curl_setopt($curl,CURLOPT_USERAGENT,$user_agent);
              //设置请求来源信息
              curl_setopt($curl,CURLOPT_AUTOREFERER,TRUE);
              //设置和ssl相关的信息
              curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
              curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
              curl_setopt($curl, CURLOPT_POST, 1);
              curl_setopt($curl, CURLOPT_POSTFIELDS, $list);
              curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
              $output = curl_exec($curl);
              curl_close($curl);
              $arr = json_decode($output,true);
             
              return($arr['accessToken']);
	}
}

?>