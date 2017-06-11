<?php
/**
 * 基本函数库
 */

/**
 * 自动加载
 */
// function __autoload($class){
// 	include_once str_replace('_', '/', strtolower($class)).'.php';
// }

/**
 * 深度去除魔术引号
 */
function deepStripslashes($value){
	return is_array($value) ? array_map('deepStripslashes', $value) : stripslashes($value);
}

/**
 * 分析请求
 */
function parseRequest(){
	$path = str_replace(dirname($_SERVER['SCRIPT_NAME']), null, $_SERVER['REQUEST_URI']);
	define('REQUEST_URI', $path);
	$path = trim( strpos($path, '?') ? substr($path, 0, strpos($path, '?')) : $path , '/');
	define('REQUEST_PATH', $path);
	$site = ((isset($_SERVER['HTTPS'])&&'on'==$_SERVER['HTTPS'])?'https://':'http://') . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']);
	define('SITE_URL', rtrim($site, '/').'/');
}

/**
 * 跳转
 */
function redirect($url){
	@ob_end_clean();
	@session_write_close();
	header('Location: '.$url);
	exit;
}

/**
 * 异常截获
 */
function exceptionHandle(Exception $e){
	syserr($e->getMessage());
}

/**
 * 系统级错误输出
 */
function syserr($msg='系统发生错误'){
	@ob_clean();
	@header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1').' 500 Internal Server Error', true, 500);
	die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>系统错误</title><style>html{padding:50px 10px;font-size:16px;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;line-height:1.4;color:#666;background:#F6F6F3}body{max-width:500px;padding:30px 20px;margin:0 auto;background:#FFF}h2{margin:0 0 20px 0}p{margin:0 0 15px 0}hr{margin-top:30px;border:none;border-top:#bbb dashed 2px}span{color:#aaa}a{color:#05AAFF;text-decoration:none}.container{max-width:380px;margin:0 auto;word-break:break-all}</style></head><body><div class="container"><h2>服务器不理你</h2><p>&gt;_&lt; 你非常幸运地逮到了服务器偷懒！</p><p>请点击<a href="javascript:location.reload();">这里</a>刷新。如果仍然显示本页面，请截图发送到<strong>dongpingeve@126.com</strong>，让我们的程序员叫醒服务器~</p><hr/><span>'.nl2br($msg).'</span></div></body></html>');
}

/**
 * 分页条
 */
function pageNav($total, $page, $limit, &$offset, &$navHtml, $amount=5, $url='?page=%d'){
	// 初始化参数
	$maxPage = max(1, ceil($total/$limit));
	$page = max(1, intval($page));
	$page = min($page, $maxPage);
	$offset = ($page-1)*$limit;
	// 无需分页
	if ($maxPage == 1){
		return;
	}
	// 左右起止页码
	$start = max(1, $page-floor($amount/2));
	$end = min($maxPage, $page+floor($amount/2));
	if ( $end-$start+1 < $amount ){
		$start = max(1, $start - ($amount - ($end-$start+1)));
		if ( $end-$start+1 < $amount ){
			$end = min($maxPage, $end + ($amount - ($end-$start+1)));
		}
	}
	// 制作分页
	$navHtml = '<div class="pagenav">'; // 开始标签
	if ($start > 1){
		$navHtml .= '<li><a href="'.sprintf($url, 1).'">1</a></li>'; // 首页
	}
	if ($page != 1){
		$navHtml .= '<li><a href="'.sprintf($url, $page-1).'"><i class="fa fa-chevron-left"></i></a></li>'; // 上一页
	}
	for ( $i=$start ; $i<=$end ; $i++ ){
		$navHtml .= '<li'.($i==$page?' class="current"':'').'><a href="'.sprintf($url, $i).'">'.$i.'</a></li>';
	}
	if ($page != $maxPage){
		$navHtml .= '<li><a href="'.sprintf($url, $page+1).'"><i class="fa fa-chevron-right"></i></a></li>'; // 下一页
	}
	$navHtml .= '</div>'; // 结束标签
}

//文件上传功能
/**
 * 得到文件扩展名
 * @param string $filename
 * @return string
 */
function getExt($filename){
	return strtolower(pathinfo($filename,PATHINFO_EXTENSION));
}


