<?php
function buildRandomString($type=1,$length=4){
	if($type==1){
		$chars=join("",range(2,9));
	}elseif($type==2){
		$chars=join("",array_merge(range("a","z"),range("A","Z")));
	}elseif($type==3){
		$chars=join("",array_merge(range("a","z"),range("A","Z"),range(2,9)));
	}
	if($length>strlen($chars)){
		exit("字符串长度不够");
	}
	$chars=str_shuffle($chars);  //随机打乱顺序
	return substr($chars,0,$length);
 }
function verifyImage($type=1,$length=4,$pixel=120,$line=4,$sess_name="verify"){
	//session_start();
	//创建画布
	$width=100;
	$height=46;
	$image=imagecreatetruecolor($width,$height);
	$white=imagecolorallocate($image,255,255,255);
	$black=imagecolorallocate($image,0,0,0);
	//用填充矩形填充画布
	imagefilledrectangle($image,1,1,$width-2,$height-2,$white);
	$chars=buildRandomString($type,$length);
	$_SESSION[$sess_name]=$chars;
	
	for($i=0; $i< $length; $i++){
		$size=mt_rand(16,18);
		$angle=mt_rand(-15,15);
		$x=20+$i*$size;
		$y=mt_rand(20,36);
		
		$fontfile = './res/fonts/arial.ttf';
		$text=substr($chars,$i,1);
		$color=imagecolorallocate($image,rand(0,120),rand(0,120),rand(0,120));
		
		imagettftext($image,$size,$angle,$x,$y,$color,$fontfile,$text);
	}
	if($pixel){
		for($i=0; $i<$pixel; $i++){
		  $pointcolor=imagecolorallocate($image,rand(50,200),rand(50,200),rand(50,200));
		  imagesetpixel($image,mt_rand(0,$width-1),mt_rand(0,$height-1),$pointcolor);
		}
	}
	if($line){
		for($i=1; $i<$line; $i++){
			$linecolor=imagecolorallocate($image,rand(80,220),rand(80,220),rand(80,220));
			imageline($image,mt_rand(0,$width-1),mt_rand(0,$height-1),mt_rand(0,$width-1),mt_rand(0,$height-1),$linecolor);
		}
	}
	header("content-type:image/gif");
	imagegif($image);
	imagedestroy($image);
}

class Controller_Verify{
	static public function verify(){
		verifyImage();
	}
	
}
?>