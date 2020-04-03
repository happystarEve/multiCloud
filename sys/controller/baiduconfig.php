<?php 
// 报告所有 PHP 错误
error_reporting(-1);

define('__BOS_CLIENT_ROOT', dirname(__DIR__));

// 设置BosClient的Access Key ID、Secret Access Key和ENDPOINT
$BOS_TEST_CONFIG =
    array(
        'credentials' => array(
            'ak' => '',
            'sk' => '',
        ),
        'endpoint' => '',
    );

$STDERR = fopen('php://stderr', 'w+');
// 设置log的格式和级别
$__handler = new \Monolog\Handler\StreamHandler($STDERR, \Monolog\Logger::DEBUG);
$__handler->setFormatter(
    new \Monolog\Formatter\LineFormatter(null, null, false, true)
);
\BaiduBce\Log\LogFactory::setInstance(
    new \BaiduBce\Log\MonoLogFactory(array($__handler))
);
\BaiduBce\Log\LogFactory::setLogLevel(\Psr\Log\LogLevel::DEBUG);
