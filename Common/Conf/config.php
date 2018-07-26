<?php
return array(
	//'配置项'=>'配置值'
	 /* 数据库设置 */

   	'DB_TYPE'               =>  'mysql',     // 数据库类型
    'DB_HOST'               =>  '120.24.48.2', // 服务器地址
    'DB_NAME'               =>  'restaurant',          // 数据库名
    'DB_USER'               =>  'yunniu165',      // 用户名
    'DB_PWD'                =>  'wj123456',          // 密码
    'DB_PORT'               =>  '3306',        // 端口
    'DB_PREFIX'             =>  '',    // 数据库表前缀
    'VAR_PAGE'              =>  'page',
    "F_KEY"                 => "CNDY", //第四方支付
//    'SHOW_ERROR_MSG'        =>  true,    // 显示错误信息

    //微信支付码前缀
    'WX_PAY_PREFIX' => array(
        "10","11","12","13","14","15"
    ),

    //支付宝支付码前缀
    'AL_PAY_PREFIX' => array(
        "28"
    ),

    //'配置项'=>'配置值'
    'TMPL_PARSE_STRING'  =>array(
        '__UP_GOODS__' => '/Public/Uploads/Goods', // 积分兑换物品图片存储路径
        '__UP_ICO__' => '/Public/Uploads/ICO', // 系统图标存储路径
        '__UP_USER__DEFINE' => '/Public/Uploads/UserDefineIco', // 用户自定义图标存储路径
    ),

    // 缩略图
    'THUMB' => array(
        // 小图
        'smaw' => 80,
        'smah' => 80,
    ),

    // 菜品分类图标用户默认图标
    "FOOD_CATE_USER_DEFINE_ICO" => '/Public/images/avatar.png',

    // 会员短信加密规则
    'SECURESTR' => '!yunniu123-',

    // 饿了么的环境，true表示沙箱环境，false表示正式环境
    "ELEME_ENVIRONMENT" => false,

    // 距离过期时间小于等于多少就refresh获取token（单位：秒）$expires_in
    // 令牌有效时间，单位秒，在令牌有效期内可以重复使用。
    // 有效期限制（access_token：沙箱环境为1天、正式环境为30天，refresh_token：沙箱环境为15天、正式环境为35天）
    "ELEME_EXPIRES_IN" => 3600*24*3,     // 沙箱设为一小时：3600，正式环境设为三天：3600*24*3

    //密钥
    "SECRET_KEY" => "A1W9E2R1TD4Y2F6H24F2G2",

    //设备类型
    "EQUIPMENT_TYPE" => ["yell","cancel","summary"],

    // 查询数据库得出的字段名该大写的大写，该小写的小写
    'DB_PARAMS' => array(\PDO::ATTR_CASE => \PDO::CASE_NATURAL),

    'AUTOLOAD_NAMESPACE'    => array('data' => 'data'),
    'BUSINESS_ID' => 25, //代理商
    'HOST_NAME' => 'http://yunniutest.cloudabull.com'
);



