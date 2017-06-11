<?php

define('DEVELOPING', true);

/**
 * 启动框架
 */
require_once 'sys/init.php';
// require_once 'aliyun/autoload.php';
require_once 'sys/aliyun-oss-php-sdk-2.0.5.phar';
include 'sys/BaiduBce.phar';
require 'sys/controller/baiduconfig.php';
include 'controller/verify.php';
include 'controller/common.php';
include 'controller/cryption.php';
include 'controller/treenode.php';

/**
 * 路由分发
 */
$path = explode('/', REQUEST_PATH);

if ($path[0]=='verify'){

	Controller_Verify::verify();

} elseif (empty($_SESSION['userId']) ){

	Controller_Common::login();

} else switch ($path[0]) {

	case '':
		Controller_Common::main();
		break;

	case 'login':
		Controller_Common::login();
		break;

	case 'logout':
		Controller_Common::logout();
		break;

	case 'baseset':
		Controller_Common::baseset();
		break;

	case 'tree':
		Controller_TreeNode::treejson();
		break;

	case 'safeset':
		Controller_Common::safeset();

	case 'checkfile':
		Controller_Common::checkfile();
		break;
		
	default:
		break;

}
