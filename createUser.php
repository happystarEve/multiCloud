<?php
/**
 *初始化用户资料
 *@returns passwordHashs 两次哈希加密的用户密码
 *@returns userSecretHash 哈希加密的用户密钥
 *@returns userSecretEncrypted 加密的用户密钥
 *@returns aliBucket 阿里云存储空间
 *@returns passwordHashs 两次哈希加密的用户密码
 */
	//阿里云
	require_once __DIR__ . '/sys/aliyun-oss-php-sdk-2.0.5.phar';

	include __DIR__ . '/sys/BaiduBce.phar';
	require __DIR__ . '/sys/controller/baiduconfig.php';

	use OSS\OssClient;
	use OSS\Core\OssException;

	use BaiduBce\BceClientConfigOptions;
	use BaiduBce\Util\Time;
	use BaiduBce\Util\MimeTypes;
	use BaiduBce\Http\HttpHeaders;
	use BaiduBce\Services\Bos\BosClient;
	
	//百度云 调用配置文件中的参数
	global $BOS_TEST_CONFIG;


	$password = "123456";
	$passwordHash = md5($password);
	$passwordHashs = md5($passwordHash);

	echo $passwordHash."<br/>";
	echo $passwordHashs."<br/>";

	$userSecret = "Eve44528119931223302X756576000";
	$userSecretHash = md5($userSecret);

	$ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
	$iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
	$userSecretEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $passwordHash, $userSecret, MCRYPT_MODE_CBC, $iv);
	$userSecretEncrypted = trim(base64_encode($iv . $userSecretEncrypted));

	echo $iv."<br/>";
	echo $ivSize."<br/>";
	echo $userSecretHash."<br/>";
	echo $userSecretEncrypted."<br/>";

	$userSecretDecrypted = base64_decode($userSecretEncrypted);
	$iv_dec = substr($userSecretDecrypted, 0, 16);
	$userSecretDecrypted = substr($userSecretDecrypted, 16);
	$userSecretDecrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $passwordHash, $userSecretDecrypted, MCRYPT_MODE_CBC, $iv_dec),"\0");

	echo $userSecretDecrypted."<br/>";

	// $rs_update = Db::update('user')->rows(array(
	// 								'userSecretEncrypted' => $userSecretEncrypted
	// 								))->where('userId=?', 1)->query();

	/*
	 *阿里云
	 */
	//使用OSS域名新建OssClient  
	$aliAccessKeyId = "OwStJvolsSv3lLEn";
	$aliAccessKeySecret = "mAOZQb8TS59vVMnSmC43zhT1QEdGCN";
	$aliEndPoint = "oss-cn-shenzhen.aliyuncs.com";
	try{
		$aliOssClient = new OssClient($aliAccessKeyId, $aliAccessKeySecret, $aliEndPoint);
	}catch(OssException $e){
		print $e->getMessage();
	}
	//创建存储空间
	$aliBucket = "eve-cloud";
	try{
		$aliOssClient->createBucket($aliBucket);
	}catch(OssException $e){
		print $e->getMessage();
	}

	/*
	 *百度云
	 */
	$baiduclient = new BosClient($BOS_TEST_CONFIG);
	$bucketName = "eve-cloud";

	//Bucket是否存在，若不存在创建Bucket
	$exist = $baiduclient->doesBucketExist($bucketName);
	if(!$exist){
	    $baiduclient->createBucket($bucketName);
	}

?>