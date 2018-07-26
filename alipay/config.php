<?php
$config = array (	
		//应用ID,您的APPID。
		'app_id' => "2016111702925397",

		//商户私钥，您的原始格式RSA私钥
		'merchant_private_key' => "MIIEogIBAAKCAQEAv3VpX0bVqlCuMQ1zuAJLJukn/YSgmlkNw6QMeyAOlVzO6/ti0YTAeZ96zxQv2BJ6N2zL1acT8Y9FXLD1x1NoYUUyd/MBvCkZFHDXtUNORyz7aoZuTUGGnGGr4CnMtyksZhVqV6L2BUycJbVt4shJB4kkSna2W/TINZ0uIJ7S4J4az+zT3JSfb0Eb+Oc8rB01sWMBJrfjVU6joYYZxmXFPD2gR95CNMYyFs0N90Yx0cSpMIlxMIKt/fgqg4f/RVMRMDLwD5Ucw4XIC0BVm3h2o+DdiUvzGysh2fMxDDEFlBQ0VhqGcK0d7CgvpJ4ecWN/U434s79xWj8znzIRETdPOwIDAQABAoIBABr24EZI5aK9BitmZ5vMxuTOBZHQ8gWK8UNSgWd91k/26DWYDAzSE4GNknBDuZFG2OuhaPvIMijdMrmhOpw+BP9eDYOTN/VWHHAijF69AfNIRIh2MmazCdBQeTJy8KADLnuaHIYHL3sDlSJCcR11c8OZ7wCFw50j1mamom55r91uaJ2rJgd0j53uHxFc6k0tILpPgmZpQLwo5taFfQlqfzSfDAEOQqnlZvh515MJCnH4XeeKNNBSz2RJjyn1pyiBNj053voVJEXDFzfNb9CrfOiPSITzWqC06uhMX3z5RPye0w+NDyNOM4kNbuIuXuL/PeZI4GRbRhEfd4ZVaEuaccECgYEA4TlgXWaCYmluXF1UH0XOWFyXHcoEMyOm+PtiOaMIi/nunlapzBtYns/s2aWgEowN0Rw83SSjmOdCYPjPBJcsSJrHdtG0GJeq6eE0Qj9SczTNHE0deh9lQnhwBI3/K5RHqcdEom8gscjbpqxrwuB4kUKk1CVYe8Yb2VoRYGA0jtECgYEA2Z7gO5bk1UcexZzoDPpEDOK9qvM0hCqCXLpSmDjmYw2ozFqmBNKW4JtybOAVBg9w17/CAE/HWTuEREH6IIHgCXkSKMEQtjWGTWo1lClM90jL8e6xEE63EowULoIOhHctrij8pV5tAJmJcbzc9S8B3sTkveZxw7LwBhH1EoPH+EsCgYAZKosdBKZPDs7ZHUiYEfnDn9z25Crh9/rhWV2ZaSE8WtoR8UcZ2nhljoA9tacUS3gDxK78Wuq43CZrpYGkVqwJFNpy6W3BTbk4VwS63k59NwowPmGr0rRC4ChMKf24ReJYEz8VE2vI0dPRZPTJmsF+Ib8/QUkI05MrD0hfx4A38QKBgEu3PWk+POv/zLMQvqQVRyv8j+U0rSb261h781THJ1F7ZSmEuJKg+qG3M/6xkF8FbEuEimk1WLSxQnnFQtOgnGRvXWQUmE3tHYc91tpv7Dl1eI/6blywJn4rgrITyh1IofoghJa83cwBn5KVFYOxEUOC2dtAnIBsd5qFEPGaLC81AoGAWx90uD6ZFSnk3lARSxkHp1T2yu+GFvGX7qkIAlzEfvdNOVZsqs/O9ySZ6vDttVap0FtH5CliUZC8otyNDUGOlI5+9WRxTUyNHlNHS9ui+DhoBIaZJlfRnXpLb/B6rOqx+Xa4TGDwxxcfeb7j6wSKFP2xvaEtanJYxV45Gl/qed4=",
		
		//异步通知地址
		'notify_url' => "http://".$_SERVER["HTTP_HOST"]."/index.php/Mobile/ding_ding/notifys",
		
		//同步跳转
		'return_url' => "http://".$_SERVER["HTTP_HOST"]."/index.php/Mobile/ding_ding/orderList",

		//编码格式
		'charset' => "UTF-8",

		//签名方式
		'sign_type'=>"RSA2",

		//支付宝网关
		'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

		//支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
		'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAv3VpX0bVqlCuMQ1zuAJLJukn/YSgmlkNw6QMeyAOlVzO6/ti0YTAeZ96zxQv2BJ6N2zL1acT8Y9FXLD1x1NoYUUyd/MBvCkZFHDXtUNORyz7aoZuTUGGnGGr4CnMtyksZhVqV6L2BUycJbVt4shJB4kkSna2W/TINZ0uIJ7S4J4az+zT3JSfb0Eb+Oc8rB01sWMBJrfjVU6joYYZxmXFPD2gR95CNMYyFs0N90Yx0cSpMIlxMIKt/fgqg4f/RVMRMDLwD5Ucw4XIC0BVm3h2o+DdiUvzGysh2fMxDDEFlBQ0VhqGcK0d7CgvpJ4ecWN/U434s79xWj8znzIRETdPOwIDAQAB",
		
	
);