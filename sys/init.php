<?php
/**
 * 初始化系统运行环境
 */
define('SYS', dirname(__FILE__).'/');
define('ROOT', dirname(SYS).'/');
defined('DEVELOPING') or define('DEVELOPING', false);

/**
 * 加载基本函数支持
 */
require_once 'common.php';

/**
 * 添加系统路径到包含路径
 */
@set_include_path( get_include_path() .PATH_SEPARATOR. SYS );

/**
 * 开始监听缓冲区
 */
session_start();
ob_start();

/**
 * 判断错误输出
 */
error_reporting(DEVELOPING ? E_ALL : 0);

/**
 * 异常截获函数
 */
set_exception_handler('exceptionHandle');

/**
 * 设置时区
 */
date_default_timezone_set('PRC');

/**
 * 处理魔术引号
 */
if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
  $_GET = deepStripslashes($_GET);
  $_POST = deepStripslashes($_POST);
  $_COOKIE = deepStripslashes($_COOKIE);
}

/**
 * 创建数据库连接
 */
$dbConf = array(
		'host' => '127.0.0.1',
		'user' => 'root',
		'pass' => '223302',
		'database' => 'cloud'
	);

if (!is_array($dbConf)) syserr('无法读取数据库配置');

include 'db.php';
Db::connect($dbConf);

/**
 * 分析请求
 */
parseRequest();
