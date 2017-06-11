<?php 
require_once __DIR__ . '/aliyunconfig.php';

use OSS\OssClient;
use OSS\Core\OssException;

class Controller_ALiYun_Common{
	const endpoint = ALiYun_Config::OSS_ENDPOINT;
    const accessKeyId = ALiYun_Config::OSS_ACCESS_ID;
    const accessKeySecret = ALiYun_Config::OSS_ACCESS_KEY;
    const bucket = ALiYun_Config::OSS_BUCKET;

    /**
     * 根据Config配置，得到一个OssClient实例
     *
     * @return OssClient 一个OssClient实例
     */
    public static function getOssClient()
    {
        try {
            $ossClient = new OssClient(self::accessKeyId, self::accessKeySecret, self::endpoint, false);
        } catch (OssException $e) {
            printf(__FUNCTION__ . "creating OssClient instance: FAILED\n");
            printf($e->getMessage() . "\n");
            return null;
        }
        return $ossClient;
    }

    public static function getBucketName()
    {
        return self::bucket;
    }

    /**
     * 工具方法，创建一个存储空间，如果发生异常直接exit
     */
    public static function createBucket()
    {
        $ossClient = self::getOssClient();
        if (is_null($ossClient)) exit(1);
        $bucket = self::getBucketName();
        $acl = OssClient::OSS_ACL_TYPE_PUBLIC_READ;
        try {
            $ossClient->createBucket($bucket, $acl);
        } catch (OssException $e) {

            $message = $e->getMessage();
            if (\OSS\Core\OssUtil::startsWith($message, 'http status: 403')) {
                echo "Please Check your AccessKeyId and AccessKeySecret" . "\n";
                exit(0);
            } elseif (strpos($message, "BucketAlreadyExists") !== false) {
                echo "Bucket already exists. Please check whether the bucket belongs to you, or it was visited with correct endpoint. " . "\n";
                exit(0);
            }
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
        print(__FUNCTION__ . ": OK" . "\n");
    }

    public static function println($message)
    {
        if (!empty($message)) {
            echo strval($message) . "\n";
        }
    }
}

/**
 * 列出Bucket内所有目录和文件, 注意如果符合条件的文件数目超过设置的max-keys， 用户需要使用返回的nextMarker作为入参，通过
 * 循环调用ListObjects得到所有的文件，具体操作见下面的 listAllObjects 示例
 *
 * @param OssClient $ossClient OssClient实例
 * @param string $bucket 存储空间名称
 * @return listObjectInfo 存储目录和文件信息
 */
function listObjects($ossClient, $bucket, $prefix)
{
    $delimiter = '/';
    $nextMarker = '';
    $maxkeys = 100;
    $options = array(
        'delimiter' => $delimiter,
        'prefix' => $prefix,
        'max-keys' => $maxkeys,
        'marker' => $nextMarker,
    );
    try {
        return $ossClient->listObjects($bucket, $options);
    } catch (OssException $e) {
        exceptionHandle($e);
        return;
    }
}
