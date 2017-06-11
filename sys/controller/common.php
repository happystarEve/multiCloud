<?php
require_once __DIR__ . '/aliyuncommon.php';

//阿里云
use OSS\OssClient;
use OSS\Core\OssException;

//百度云
use BaiduBce\BceClientConfigOptions;
use BaiduBce\Util\Time;
use BaiduBce\Util\MimeTypes;
use BaiduBce\Http\HttpHeaders;
use BaiduBce\Services\Bos\BosClient;

//调用配置文件中的参数
global $BOS_TEST_CONFIG;

//创建百度云client
$GLOBALS['baiduClient'] = new BosClient($BOS_TEST_CONFIG);

class Controller_Common {
	static public function main(){
		if(!empty($_SESSION)){
			//获取默认的安全策略、存储空间的名称
			$rs_security = Db::select('securityName','securityId','bucketName')
					->from('security','user')
					->join('user','security.securityId = user.selectedSecurity','RIGHT')
					->where('userId=?', $_SESSION['userId'])
					->limit(1)
					->fetch();
			$securityName = $rs_security["securityName"];
			$bucketName = $rs_security["bucketName"];

			//获取阿里云bucket
			$aliOssClient = Controller_ALiYun_Common::getOssClient();
			if (is_null($aliOssClient)) exit(1);
			try{
				$aliBucketListInfo = $aliOssClient->listBuckets();
			}catch(OssException $e){
				exceptionHandle($e);
				return;
			}
			$aliBucketList = $aliBucketListInfo->getBucketList();
			foreach($aliBucketList as $aliBucket){
				if($aliBucket->getName() == $bucketName){
					$aliBucketName = $bucketName;
					break;
				}
			}

			//获取百度云bucket
			$exist = $GLOBALS['baiduClient']->doesBucketExist($bucketName);
			if($exist){
				$baiduBucketName = $bucketName;
			}

			//获得用户密钥
			$rs_usersecret = Db::select('userName','passwordHashs','userSecretHash','userSecretEncrypted')
					->from('user')
					->where('userId=?', $_SESSION['userId'])
					->limit(1)
					->fetch();
			$userSecretDecrypted = Controller_Cryption::decryption($_SESSION["passwordHash"], $rs_usersecret["userSecretEncrypted"]);



			//获取文件夹里面的内容
			if(!empty($_GET['folderName'])){
				//列出所有文件夹
				$sql_file = Db::select('objectName','objectSize','objectDate','objectId')
						->from('object')
						->where('userId=?', $_SESSION['userId'])
						->where('objectName like ?', $_GET['folderName'].'%/')
						->where('objectName NOT like ?', $_GET['folderName'].'%/%/');
				
				$sql_file->order('objectDate','DESC');
				$rs_file  = Db::fetchAll(Db::query($sql_file));

				//列出所有文件
				$sql_object = Db::select('objectName','objectSize','objectDate','objectId')
						->from('object')
						->where('userId=?', $_SESSION['userId'])
						->where('objectName like ?', $_GET['folderName'].'%')
						->where('objectName NOT LIKE ?', $_GET['folderName'].'%/')
						->where('objectName NOT like ?', $_GET['folderName'].'%/%')
						->where('objectName !=?',$_GET['folderName']);
				
				$sql_object->order('objectDate','DESC');
				$rs_object  = Db::fetchAll(Db::query($sql_object));
			}else{
				//列出所有文件夹
				$sql_file = Db::select('objectName','objectSize','objectDate','objectId')
						->from('object')
						->where('userId=?', $_SESSION['userId'])
						->where('objectName like ?', '%/')
						->where('objectName NOT like ?', '%/%/');
				
				$sql_file->order('objectDate','DESC');
				$rs_file  = Db::fetchAll(Db::query($sql_file));

				//列出所有文件
				$sql_object = Db::select('objectName','objectSize','objectDate','objectId')
						->from('object')
						->where('userId=?', $_SESSION['userId'])
						->where('objectName NOT LIKE ?', '%/')
						->where('objectName NOT LIKE ?', '%/%');
			
				$sql_object->order('objectDate','DESC');
				$rs_object  = Db::fetchAll(Db::query($sql_object));
			}

			//搜索
			if(!empty($_GET['kw'])){
				//列出所有符合条件的文件夹
				$sql_file = Db::select('objectName','objectSize','objectDate','objectId')
						->from('object')
						->where('userId=?', $_SESSION['userId'])
						->where('objectName like ?', '%'.$_GET['kw'].'%/');
				
				$sql_file->order('objectDate','DESC');
				$rs_file  = Db::fetchAll(Db::query($sql_file));

				//列出所有文件
				$sql_object = Db::select('objectName','objectSize','objectDate','objectId')
						->from('object')
						->where('userId=?', $_SESSION['userId'])
						->where('objectName like ?', '%'.$_GET['kw'].'%')
						->where('objectName NOT LIKE ?', '%'.$_GET['kw'].'%/');
			
				$sql_object->order('objectDate','DESC');
				$rs_object  = Db::fetchAll(Db::query($sql_object));
			}

			//新建文件夹
			if(!empty($_POST['newfile'])){
				if(!empty($_GET['folderName'])){
					$newfile = $_GET['folderName'].$_POST['newfile'];
				}else{
					$newfile = $_POST['newfile'];
				}
				$folderList = explode('/', $newfile);
				$folderListLength = count($folderList);
				$postFolder = $_POST['newfile'];
				//构建文件夹树 第一级文件夹
				if($folderListLength == 1){
					//判断是否已经存在该文件夹
					$rs_folderExist = Db::select('*')
								->from('foldertree')
								->where('folderName=?', $postFolder)
								->where('parentId=?', 1)
								->fetch();
					if(!$rs_folderExist){
						$folderExist = false;
					}else{
						$folderExist = true;
						$rs_ExistSeq = Db::select('folderName')
								->from('foldertree')
								->where('parentId=?', 1)
								->where('folderName like ?',$postFolder.'(%)')
								->where('folderName not like?',$postFolder.'(%)(%)')
								->fetchAll();
						if(!$rs_ExistSeq){
							$postFolder = $postFolder."(1)";
						}else{
							$seqArray = array();
							foreach($rs_ExistSeq as $row_ExistSeq):
								$leftHalfPos = strrpos($row_ExistSeq['folderName'],"(");
								$rightHalfPos = strrpos($row_ExistSeq['folderName'],")");
								$seq = substr($row_ExistSeq['folderName'], $leftHalfPos+1, $rightHalfPos-$leftHalfPos-1);
								array_push($seqArray, $seq);
							endforeach;
							//冒泡排序
							$cnt = count($seqArray);
							for($i = 0; $i < $cnt; $i++){
								for($j = 0; $j < $cnt - $i - 1; $j++){
									if($seqArray[$j] > $seqArray[$j+1]){
										$temp = $seqArray[$j];
										$seqArray[$j] = $seqArray[$j+1];
										$seqArray[$j+1] = $temp;
									}
								}
							}
							for($i = 1, $j = 0; $j < $cnt; $j++,$i++){
								if($i != $seqArray[$j])
									break;
							}
							$postFolder = $postFolder."(".$i.")";
						}
					}
					$rs_folder = Db::insert('foldertree') -> rows(array(
								'parentId' => 1,
								'folderName' => $postFolder
					))->query();
				}else{
					$rs_parentId = Db::select('folderId')
							->from('foldertree')
							->where('folderName=?',$folderList[$folderListLength-2])
							->fetchAll();
					foreach($rs_parentId as $row_parentId):
						$path = Controller_Treenode::get_path($row_parentId['folderId']);
						if($folderListLength == 2 && count($path) == 2){
							$parentId = $row_parentId['folderId'];
							break;
						}
						$foldercount = 0;
						//选出所选择的文件夹
						//path 多出 0和全部文件   folderList 多出本身 父亲
						if(count($path) == $folderListLength){
							for($i=2, $j=0; $i<count($path); $i++,$j++){
								if($path[$i]['folderName'] == $folderList[$j]){
									$foldercount++;
								}
							}
							if($foldercount == count($path)-2){
								$parentId = $row_parentId['folderId'];
								break;
							}
						}
					endforeach;
					//判断是否已经存在该文件夹
					$rs_folderExist = Db::select('*')
								->from('foldertree')
								->where('folderName=?', $postFolder)
								->where('parentId=?', $parentId)
								->fetch();
					if(!$rs_folderExist){						
						$folderExist = false;
					}else{
						$folderExist = true;
						$rs_ExistSeq = Db::select('folderName')
								->from('foldertree')
								->where('parentId=?', $parentId)
								->where('folderName like ?',$postFolder.'(%)')
								->where('folderName not like?',$postFolder.'(%)(%)')
								->fetchAll();
						if(!$rs_ExistSeq){
							$postFolder = $postFolder."(1)";
						}else{
							$seqArray = array();
							foreach($rs_ExistSeq as $row_ExistSeq):
								$leftHalfPos = strrpos($row_ExistSeq['folderName'],"(");
								$rightHalfPos = strrpos($row_ExistSeq['folderName'],")");
								$seq = substr($row_ExistSeq['folderName'], $leftHalfPos+1, $rightHalfPos-$leftHalfPos-1);
								array_push($seqArray, $seq);
							endforeach;
							//冒泡排序
							$cnt = count($seqArray);
							for($i = 0; $i < $cnt; $i++){
								for($j = 0; $j < $cnt - $i - 1; $j++){
									if($seqArray[$j] > $seqArray[$j+1]){
										$temp = $seqArray[$j];
										$seqArray[$j] = $seqArray[$j+1];
										$seqArray[$j+1] = $temp;
									}
								}
							}
							for($i = 1, $j = 0; $j < $cnt; $j++,$i++){
								if($i != $seqArray[$j])
									break;
							}
							$postFolder = $postFolder."(".$i.")";
						}
					}	
					$rs_folder = Db::insert('foldertree') -> rows(array(
							'parentId' => $parentId,
							'folderName' => $postFolder
					))->query();
				}
				//如果文件夹已存在  文件夹名添加编号
				if($folderExist){
					$rs_ExistSeq = Db::select('securityName','objectName','objectId')
							->from('security','object')
							->join('object','security.securityId = object.securityId','RIGHT')
							->where('objectName like ?',$newfile.'(%)/')
							->where('objectName not like?',$newfile.'(%)(%)/')
							->fetchAll();
					if(!$rs_ExistSeq){
						$newfile = $newfile."(1)";
					}else{
						$seqArray = array();
						foreach($rs_ExistSeq as $row_ExistSeq):
							$leftHalfPos = strrpos($row_ExistSeq['objectName'],"(");
							$rightHalfPos = strrpos($row_ExistSeq['objectName'],")");
							$seq = substr($row_ExistSeq['objectName'], $leftHalfPos+1, $rightHalfPos-$leftHalfPos-1);
							array_push($seqArray, $seq);
						endforeach;
						//冒泡排序
						$cnt = count($seqArray);
						for($i = 0; $i < $cnt; $i++){
							for($j = 0; $j < $cnt - $i - 1; $j++){
								if($seqArray[$j] > $seqArray[$j+1]){
									$temp = $seqArray[$j];
									$seqArray[$j] = $seqArray[$j+1];
									$seqArray[$j+1] = $temp;
								}
							}
						}
						for($i = 1, $j = 0; $j < $cnt; $j++,$i++){
							if($i != $seqArray[$j])
								break;
						}
						$newfile = $newfile."(".$i.")";
					}
				}
				try {
					$aliOssClient->createObjectDir($aliBucketName, $newfile);
				} catch (OssException $e) {
					exceptionHandle($e);
					return;
				}
				$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $newfile.'/', '');

				$rs_insert = Db::insert('object') ->rows(array(
					'userId' => $_SESSION['userId'],
					'securityId' => 3,
					'objectName' => $newfile.'/',
					'dataSecretEncrypted' => '',
					'objectSize' => 0,
					'objectDate' => time(),
				))->query();
				if($rs_insert){
					echo "<script>alert('添加成功！');</script>";
					echo "<script>window.location='".SITE_URL."'</script>";
				}else{
					echo "<script>alert('添加失败！');</script>";
					echo "<script>window.location='".SITE_URL."'</script>";
				}				
			}
			//上传文件
			if(!empty($_FILES)){	
				//随机生成数据密钥
				$dataSecret = substr(md5(time()),0,16);
				//用$userSecret加密$dataSecret
				$dataSecretEncrypted = Controller_Cryption::encryption($userSecretDecrypted, $dataSecret);
				// 获得文件名称
				if(!empty($_GET['folderName'])){
					$fileName = $_GET['folderName'].$_FILES['uploadfile']['name'];
				}else{
					$fileName = $_FILES['uploadfile']['name'];
				}
				$rs_Exist = Db::select('securityName','objectName','objectId')
							->from('security','object')
							->join('object','security.securityId = object.securityId','RIGHT')
							->where('objectName=?',$fileName)
							->fetch();
				//若已存在文件 为文件添加编号
				if($rs_Exist){
					$fileLastDotPos = strripos($fileName, '.');
					$rs_ExistSeq = Db::select('securityName','objectName','objectId')
							->from('security','object')
							->join('object','security.securityId = object.securityId','RIGHT')
							->where('objectName like ?',substr($fileName, 0, $fileLastDotPos).'(%).%')
							->where('objectName not like?',substr($fileName, 0, $fileLastDotPos).'(%)(%).%')
							->fetchAll();
					if(!$rs_ExistSeq){
						$fileName = substr($fileName, 0, $fileLastDotPos)."(1)".substr($fileName, $fileLastDotPos);
					}else{
						$seqArray = array();
						foreach($rs_ExistSeq as $row_ExistSeq):
							$leftHalfPos = strrpos($row_ExistSeq['objectName'],"(");
							$rightHalfPos = strrpos($row_ExistSeq['objectName'],")");
							$seq = substr($row_ExistSeq['objectName'], $leftHalfPos+1, $rightHalfPos-$leftHalfPos-1);
							array_push($seqArray, $seq);
						endforeach;
						//冒泡排序
						$cnt = count($seqArray);
						for($i = 0; $i < $cnt; $i++){
							for($j = 0; $j < $cnt - $i - 1; $j++){
								if($seqArray[$j] > $seqArray[$j+1]){
									$temp = $seqArray[$j];
									$seqArray[$j] = $seqArray[$j+1];
									$seqArray[$j+1] = $temp;
								}
							}
						}
						for($i = 1, $j = 0; $j < $cnt; $j++,$i++){
							if($i != $seqArray[$j])
								break;
						}
						$fileName = substr($fileName, 0, $fileLastDotPos)."(".$i.")".substr($fileName, $fileLastDotPos);
					}
				}
				// 文件全加密
				$data_en = Controller_Cryption::fileencryption($_FILES['uploadfile']['tmp_name'], $dataSecret);
				$dataSize = strlen($data_en);
				$time = time();
				//分块上传 大于15M
				if($dataSize > 15728640){
					Controller_Cryption::saveToFile(__DIR__.'/'.$_FILES['uploadfile']['name'],$data_en);
					switch($securityName){
						//存在百度云
						case 'baiduCloud':
							//初始化分块上传
							$response = $GLOBALS['baiduClient']->initiateMultipartUpload($baiduBucketName, $fileName);
							$uploadId =$response->uploadId;
							//设置分块的开始偏移位置
							$offset = 0;
							$partNumber = 1;
							//设置每块为5MB
							$partSize = 5 * 1024 * 1024;
							$length = $partSize;
							$partList = array();
							$bytesLeft = $dataSize;
							$e_tags = array();
							//分块上传
							while ($bytesLeft > 0) {
							    $length = ($length > $bytesLeft) ? $bytesLeft : $length;
							    $response = $GLOBALS['baiduClient']->uploadPartFromFile($baiduBucketName, $fileName, $uploadId,  $partNumber, __DIR__.'/'.$_FILES['uploadfile']['name'], $offset, $length);
							    array_push($partList, array("partNumber"=>$partNumber, "eTag"=>$response->metadata["etag"]));
							    $offset += $length;
							    $partNumber++;
							    $bytesLeft -= $length;
							}
							//完成分块上传
							$response = $GLOBALS['baiduClient']->completeMultipartUpload($baiduBucketName, $fileName, $uploadId, $partList);
							
							$rs_baiduobject = Db::insert('object')->rows(array(
								'userId' => $_SESSION['userId'],
								'securityId' => $rs_security['securityId'],
								'objectName' => $fileName,
								'dataSecretEncrypted' => $dataSecretEncrypted,
								'objectSize' => $dataSize,
								'objectDate' => $time,
							))->query();
							if($rs_baiduobject){
								echo "<script>alert('上传成功！');</script>";
								if(!unlink(__DIR__.'/'.$_FILES['uploadfile']['name'])){
									echo "<script>alert('Error deleting ".__DIR__.'/'.$_FILES['uploadfile']['name']." on Server');</script>";
								}
								echo "<script>window.location='".SITE_URL."'</script>";
							}else{
								echo "<script>alert('上传失败！');</script>";
								if(!unlink(__DIR__.'/'.$_FILES['uploadfile']['name'])){
									echo "<script>alert('Error deleting ".__DIR__.'/'.$_FILES['uploadfile']['name']." on Server');</script>";
								}
								echo "<script>window.location='".SITE_URL."'</script>";
							}	
							
							break;

						//存在阿里云
						case 'aliCloud':
							// 分步上传
							$options = array();
							try{
								$aliOssClient->multiuploadFile($aliBucketName, $fileName, __DIR__.'/'.$_FILES['uploadfile']['name'], $options);
							} catch (OssException $e) {
						        exceptionHandle($e);
						        return;
							}	
							
							//插入数据库
							$rs_aliobject = Db::insert('object') ->rows(array(
								'userId' => $_SESSION['userId'],
								'securityId' => $rs_security['securityId'],
								'objectName' => $fileName,
								'dataSecretEncrypted' => $dataSecretEncrypted,
								'objectSize' => $dataSize,
								'objectDate' => $time,
							))->query();
							if($rs_aliobject){
								echo "<script>alert('上传成功！');</script>";
								if(!unlink(__DIR__.'/'.$_FILES['uploadfile']['name'])){
									echo "<script>alert('Error deleting ".__DIR__.'/'.$_FILES['uploadfile']['name']." on Server');</script>";
								}
								echo "<script>window.location='".SITE_URL."'</script>";
							}else{
								echo "<script>alert('上传失败！');</script>";
								if(!unlink(__DIR__.'/'.$_FILES['uploadfile']['name'])){
									echo "<script>alert('Error deleting ".__DIR__.'/'.$_FILES['uploadfile']['name']." on Server');</script>";
								}
								echo "<script>window.location='".SITE_URL."'</script>";
							}
								
							break;

						//分别存在百度云、阿里云
						case 'dualStorage':
							//百度云初始化分块上传
							$response = $GLOBALS['baiduClient']->initiateMultipartUpload($baiduBucketName, $fileName);
							$uploadId =$response->uploadId;
							//设置分块的开始偏移位置
							$offset = 0;
							$partNumber = 1;
							//设置每块为5MB
							$partSize = 5 * 1024 * 1024;
							$length = $partSize;
							$partList = array();
							$bytesLeft = $dataSize;
							$e_tags = array();
							//分块上传
							while ($bytesLeft > 0) {
							    $length = ($length > $bytesLeft) ? $bytesLeft : $length;
							    $response = $GLOBALS['baiduClient']->uploadPartFromFile($baiduBucketName, $fileName, $uploadId,  $partNumber, __DIR__.'/'.$_FILES['uploadfile']['name'], $offset, $length);
							    array_push($partList, array("partNumber"=>$partNumber, "eTag"=>$response->metadata["etag"]));
							    $offset += $length;
							    $partNumber++;
							    $bytesLeft -= $length;
							}
							//完成分块上传
							$response = $GLOBALS['baiduClient']->completeMultipartUpload($baiduBucketName, $fileName, $uploadId, $partList);

							//阿里云分步上传
							$options = array();
							try{
								$aliOssClient->multiuploadFile($aliBucketName, $fileName, __DIR__.'/'.$_FILES['uploadfile']['name'], $options);
							} catch (OssException $e) {
						        exceptionHandle($e);
						        return;
							}
							
							//插入数据库
							$rs_dualobject = Db::insert('object')->rows(array(
								'userId' => $_SESSION['userId'],
								'securityId' => $rs_security['securityId'],
								'objectName' => $fileName,
								'dataSecretEncrypted' => $dataSecretEncrypted,
								'objectSize' => $dataSize,
								'objectDate' => $time,
							))->query();						
							if($rs_dualobject){
								echo "<script>alert('上传成功！');</script>";
								if(!unlink(__DIR__.'/'.$_FILES['uploadfile']['name'])){
									echo "<script>alert('Error deleting ".__DIR__.'/'.$_FILES['uploadfile']['name']." on Server');</script>";
								}	
								echo "<script>window.location='".SITE_URL."'</script>";
							}else{
								echo "<script>alert('上传失败！');</script>";
								if(!unlink(__DIR__.'/'.$_FILES['uploadfile']['name'])){
									echo "<script>alert('Error deleting ".__DIR__.'/'.$_FILES['uploadfile']['name']." on Server');</script>";
								}	
								echo "<script>window.location='".SITE_URL."'</script>";
							}
							
							break;

						//百度云、阿里云各存一半
						case 'halfStorage':
							$data_baidu = substr($data_en, 0, strlen($data_en)/2);
							$data_ali = substr($data_en, strlen($data_en)/2);
							Controller_Cryption::saveToFile(__DIR__.'/'.$_FILES['uploadfile']['name'].'_1',$data_baidu);
							Controller_Cryption::saveToFile(__DIR__.'/'.$_FILES['uploadfile']['name'].'_2',$data_ali);

							//百度云初始化分块上传
							$response = $GLOBALS['baiduClient']->initiateMultipartUpload($baiduBucketName, $fileName);
							$uploadId =$response->uploadId;
							//设置分块的开始偏移位置
							$offset = 0;
							$partNumber = 1;
							//设置每块为5MB
							$partSize = 5 * 1024 * 1024;
							$length = $partSize;
							$partList = array();
							$bytesLeft = $dataSize/2;
							$e_tags = array();
							//分块上传
							while ($bytesLeft > 0) {
							    $length = ($length > $bytesLeft) ? $bytesLeft : $length;
							    $response = $GLOBALS['baiduClient']->uploadPartFromFile($baiduBucketName, $fileName, $uploadId,  $partNumber, __DIR__.'/'.$_FILES['uploadfile']['name'].'_1', $offset, $length);
							    array_push($partList, array("partNumber"=>$partNumber, "eTag"=>$response->metadata["etag"]));
							    $offset += $length;
							    $partNumber++;
							    $bytesLeft -= $length;
							}
							//完成分块上传
							$response = $GLOBALS['baiduClient']->completeMultipartUpload($baiduBucketName, $fileName, $uploadId, $partList);

							//阿里云分步上传
							$options = array();
							try{
								$aliOssClient->multiuploadFile($aliBucketName, $fileName, __DIR__.'/'.$_FILES['uploadfile']['name'].'_2', $options);
							} catch (OssException $e) {
						        exceptionHandle($e);
						        return;
							}
							
							//插入数据库
							$rs_halfobject = Db::insert('object')->rows(array(
								'userId' => $_SESSION['userId'],
								'securityId' => $rs_security['securityId'],
								'objectName' => $fileName,
								'dataSecretEncrypted' => $dataSecretEncrypted,
								'objectSize' => $dataSize,
								'objectDate' => $time,
							))->query();						
							if($rs_halfobject){
								echo "<script>alert('上传成功！');</script>";
								if(!unlink(__DIR__.'/'.$_FILES['uploadfile']['name'].'_1')){
									echo "<script>alert('Error deleting ".__DIR__.'/'.$_FILES['uploadfile']['name']." on Server');</script>";
								}
								if(!unlink(__DIR__.'/'.$_FILES['uploadfile']['name'].'_2')){
									echo "<script>alert('Error deleting ".__DIR__.'/'.$_FILES['uploadfile']['name']." on Server');</script>";
								}	
								echo "<script>window.location='".SITE_URL."'</script>";
							}else{
								echo "<script>alert('上传失败！');</script>";
								if(!unlink(__DIR__.'/'.$_FILES['uploadfile']['name'].'_1')){
									echo "<script>alert('Error deleting ".__DIR__.'/'.$_FILES['uploadfile']['name']." on Server');</script>";
								}
								if(!unlink(__DIR__.'/'.$_FILES['uploadfile']['name'].'_2')){
									echo "<script>alert('Error deleting ".__DIR__.'/'.$_FILES['uploadfile']['name']." on Server');</script>";
								}	
								echo "<script>window.location='".SITE_URL."'</script>";
							}
							
							break;

						default:
							break;
					}

				}else{
					switch($securityName){
						//存在百度云
						case 'baiduCloud':
							$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $fileName, $data_en);
							
							$rs_baiduobject = Db::insert('object')->rows(array(
								'userId' => $_SESSION['userId'],
								'securityId' => $rs_security['securityId'],
								'objectName' => $fileName,
								'dataSecretEncrypted' => $dataSecretEncrypted,
								'objectSize' => $dataSize,
								'objectDate' => $time,
							))->query();
							if($rs_baiduobject){
								echo "<script>alert('上传成功！');</script>";
								echo "<script>window.location='".SITE_URL."'</script>";
							}else{
								echo "<script>alert('上传失败！');</script>";
								echo "<script>window.location='".SITE_URL."'</script>";
							}	
							break;

						//存在阿里云
						case 'aliCloud':
							// 简单上传变量的内容到文件
							try{
								$aliOssClient->putObject($aliBucketName, $fileName, $data_en);
							} catch (OssException $e) {
						        exceptionHandle($e);
						        return;
							}	
							
							//插入数据库
							$rs_aliobject = Db::insert('object') ->rows(array(
								'userId' => $_SESSION['userId'],
								'securityId' => $rs_security['securityId'],
								'objectName' => $fileName,
								'dataSecretEncrypted' => $dataSecretEncrypted,
								'objectSize' => $dataSize,
								'objectDate' => $time,
							))->query();
							if($rs_aliobject){
								echo "<script>alert('上传成功！');</script>";
								echo "<script>window.location='".SITE_URL."'</script>";
							}else{
								echo "<script>alert('上传失败！');</script>";
								echo "<script>window.location='".SITE_URL."'</script>";
							}	
							break;

						//分别存在百度云、阿里云
						case 'dualStorage':
							$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $fileName, $data_en);
							try{
								$aliOssClient->putObject($aliBucketName, $fileName, $data_en);
							} catch (OssException $e) {
						        exceptionHandle($e);
						        return;
							}	
							
							//插入数据库
							$rs_dualobject = Db::insert('object')->rows(array(
								'userId' => $_SESSION['userId'],
								'securityId' => $rs_security['securityId'],
								'objectName' => $fileName,
								'dataSecretEncrypted' => $dataSecretEncrypted,
								'objectSize' => $dataSize,
								'objectDate' => $time,
							))->query();						
							if($rs_dualobject){
								echo "<script>alert('上传成功！');</script>";
								echo "<script>window.location='".SITE_URL."'</script>";
							}else{
								echo "<script>alert('上传失败！');</script>";
								echo "<script>window.location='".SITE_URL."'</script>";
							}	
							break;

						//百度云、阿里云各存一半
						case 'halfStorage':
							$data_baidu = substr($data_en, 0, strlen($data_en)/2);
							$data_ali = substr($data_en, strlen($data_en)/2);
							$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $fileName, $data_baidu);
							try{
								$aliOssClient->putObject($aliBucketName, $fileName, $data_ali);
							} catch (OssException $e) {
						        exceptionHandle($e);
						        return;
							}	
							
							//插入数据库
							$rs_halfobject = Db::insert('object')->rows(array(
								'userId' => $_SESSION['userId'],
								'securityId' => $rs_security['securityId'],
								'objectName' => $fileName,
								'dataSecretEncrypted' => $dataSecretEncrypted,
								'objectSize' => $dataSize,
								'objectDate' => $time,
							))->query();						
							if($rs_halfobject){
								echo "<script>alert('上传成功！');</script>";
								echo "<script>window.location='".SITE_URL."'</script>";
							}else{
								echo "<script>alert('上传失败！');</script>";
								echo "<script>window.location='".SITE_URL."'</script>";
							}	
							break;

						default:
							break;
					}
				}				
			}
			//获取Id 下载文件
			if(!empty($_GET['Id'])){
				$rs_download = Db::select('dataSecretEncrypted','securityName','objectName')
							->from('security','object')
							->join('object','security.securityId = object.securityId','RIGHT')
							->where('objectId=?',$_GET['Id'])
							->limit(1)
							->fetch();

				$objectList = explode('/', $rs_download['objectName']);
				$dlprefix = $objectList[count($objectList)-1];
				//下载文件夹
				if($dlprefix == ""){
					$rs_fileDownload = Db::select('dataSecretEncrypted','securityName','objectName')
									->from('security','object')
									->join('object','security.securityId = object.securityId','RIGHT')
									->where('objectName like ?','%'.$objectList[count($objectList)-2].'/%')
									->fetchAll();
					$folderprefix = $objectList[count($objectList)-2];
					foreach($rs_fileDownload as $row_fileDownload):
						//判断是否是同一个文件夹里的内容						
						if(strpos($row_fileDownload['objectName'],$rs_download['objectName'])!=0)
							continue;

						$fileList = explode('/', $row_fileDownload['objectName']);
						$folderLength = strlen($rs_download['objectName'])-strlen($folderprefix)-1;
						$folderName = substr($row_fileDownload['objectName'], $folderLength);
						$folderName = __DIR__.'/'.$folderName;

						// 如果不是文件夹，需要解密
						if($fileList[count($fileList)-1] != ""){
							$dataSecretDecrypted = Controller_Cryption::decryption($userSecretDecrypted, $row_fileDownload['dataSecretEncrypted']);
							switch ($row_fileDownload['securityName']) {
								//文件存在百度云
								case 'baiduCloud':
									//下载object到本地变量
									$content = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $row_fileDownload['objectName']);
									//解密
									Controller_Cryption::filedecryption($content, $dataSecretDecrypted, $folderName);
									break;
								
								//文件存在阿里云 && 文件分别存在百度云和阿里云
								case 'aliCloud':
								case 'dualStorage':
									// 下载object到本地变量
									$content = $aliOssClient->getObject($aliBucketName, $row_fileDownload['objectName']);
									// 解密
									Controller_Cryption::filedecryption($content, $dataSecretDecrypted, $folderName);
									break;

								//百度云、阿里云各存一半
								case 'halfStorage':
									// 下载object到本地变量
									$content_1 = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $row_fileDownload['objectName']);
									$content_2 = $aliOssClient->getObject($aliBucketName, $row_fileDownload['objectName']);
									$content = $content_1.$content_2;
									// 解密
									Controller_Cryption::filedecryption($content, $dataSecretDecrypted, $folderName);
									break;

								default:
									break;
							}	
						}else{
							$folderName = iconv("utf-8","gb2312",$folderName);
							Controller_Fileutil::createDir($folderName);
						}
					endforeach;
					//PHP压缩文件夹为zip压缩文件
					Controller_Cryption::zipFolder(__DIR__.'/'.$folderprefix, __DIR__.'/'.$folderprefix.'.zip');
					//下载文件到本地下载文件夹
					Controller_Cryption::downloadFile(__DIR__.'/', $folderprefix.'.zip');
					//删除服务器上的文件夹
					Controller_Fileutil::unlinkDir(__DIR__.'/'.$folderprefix);
				}else{
					$dataSecretDecrypted = Controller_Cryption::decryption($userSecretDecrypted, $rs_download['dataSecretEncrypted']);

					switch ($rs_download['securityName']) {
						//文件存在百度云
						case 'baiduCloud':
							//下载object到本地变量
							$content = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $rs_download['objectName']);
							//解密
							Controller_Cryption::filedecryption($content, $dataSecretDecrypted, __DIR__.'/'.$dlprefix);
							//下载文件到本地下载文件夹
							Controller_Cryption::downloadFile(__DIR__.'/', $dlprefix);
							break;
						
						//文件存在阿里云 && 文件分别存在百度云和阿里云
						case 'aliCloud':
						case 'dualStorage':
							// 下载object到本地变量
							$content = $aliOssClient->getObject($aliBucketName, $rs_download['objectName']);
							// 解密
							Controller_Cryption::filedecryption($content, $dataSecretDecrypted, __DIR__.'/'.$dlprefix);
							//下载文件到本地下载文件夹
							Controller_Cryption::downloadFile(__DIR__.'/', $dlprefix);
							break;

						//百度云、阿里云各存一半
						case 'halfStorage':
							// 下载object到本地变量
							$content_1 = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $rs_download['objectName']);
							$content_2 = $aliOssClient->getObject($aliBucketName, $rs_download['objectName']);
							$content = $content_1.$content_2;
							// 解密
							Controller_Cryption::filedecryption($content, $dataSecretDecrypted, __DIR__.'/'.$dlprefix);
							//下载文件到本地下载文件夹
							Controller_Cryption::downloadFile(__DIR__.'/', $dlprefix);
							break;

						default:
							break;
					}	
				}		
			}
			//获取删除Id  删除文件
			if(!empty($_GET['delId'])){
				$delList = explode(',', $_GET['delId']);
				for($i=0; $i<count($delList)-1; $i++){
					$rs_del = Db::select('securityName','objectName')
							->from('security','object')
							->join('object','security.securityId = object.securityId','RIGHT')
							->where('objectId=?',$delList[$i])
							->limit(1)
							->fetch();
					$delObjectList = explode('/', $rs_del['objectName']);
					$delprefix = $delObjectList[count($delObjectList)-1];
					//删除文件夹
					if($delprefix == ""){
						$rs_fileDel = Db::select('securityName', 'objectName', 'objectId')
									->from('security','object')
									->join('object', 'security.securityId = object.securityId', 'RIGHT')
									->where('objectName like ?', '%'.$delObjectList[count($delObjectList)-2].'/%')
									->where('objectName like ?', $rs_del['objectName'].'%')
									->fetchAll();
						//删除对应文件夹树
						//第一级文件夹
						if(count($delObjectList) == 2){
							$rs_selfId = Db::select('folderId')
										->from('foldertree')
										->where('folderName=?',$delObjectList[count($delObjectList)-2])
										->where('parentId=?', 1)
										->fetch();
							$selfId = $rs_selfId['folderId'];
						}else{
							$rs_selfId = Db::select('folderId')
									->from('foldertree')
									->where('folderName=?',$delObjectList[count($delObjectList)-2])
									->fetchAll();
							foreach($rs_selfId as $row_selfId):
								$path = Controller_Treenode::get_path($row_selfId['folderId']);
								//选出所选择的文件夹
								//path 多出 0和全部文件   delObjectList 多出本身和空格
								if(count($path) == count($delObjectList)){
									$foldercount = 0;
									for($i=2, $j=0; $i<count($path); $i++,$j++){
										if($path[$i]['folderName'] == $delObjectList[$j]){
											$foldercount++;
										}
									}
									if($foldercount == count($path)-2){
										$selfId = $row_selfId['folderId'];
										break;
									}
								}
							endforeach;
						}
						Controller_Treenode::delete_children($selfId);
						$rs_folderDel = Db::delete('foldertree')->where('parentId=?',$selfId)->query();
						$rs_folderSelfDel = Db::delete('foldertree')->where('folderId=?',$selfId)->query();
						foreach($rs_fileDel as $row_fileDel):
							switch ($row_fileDel['securityName']) {
								//文件存在百度云
								case 'baiduCloud':
									$GLOBALS['baiduClient']->deleteObject($baiduBucketName, $row_fileDel['objectName']);
									break;
								//存在阿里云
								case 'aliCloud':
									try{
										$aliOssClient->deleteObject($aliBucketName, $row_fileDel['objectName']);
									} catch (OssException $e) {
								        exceptionHandle($e);
								        return;
									}
									break;
								//分别存在百度云、阿里云 && 一半存在百度云，一半存在阿里云
								case 'dualStorage':
								case 'halfStorage':
									$GLOBALS['baiduClient']->deleteObject($baiduBucketName, $row_fileDel['objectName']);
									try{
										$aliOssClient->deleteObject($aliBucketName, $row_fileDel['objectName']);
									} catch (OssException $e) {
								        exceptionHandle($e);
								        return;
									}
									break;
								default:
									break;
							}
							$rs_filedelsql = Db::delete('object')->where('objectId=?',$row_fileDel['objectId'])->query();
						endforeach;
					}else{
						switch ($rs_del['securityName']) {
							//文件存在百度云
							case 'baiduCloud':
								$GLOBALS['baiduClient']->deleteObject($baiduBucketName, $rs_del['objectName']);
								break;
							//存在阿里云
							case 'aliCloud':
								try{
									$aliOssClient->deleteObject($aliBucketName, $rs_del['objectName']);
								} catch (OssException $e) {
							        exceptionHandle($e);
							        return;
								}
								break;
							//分别存在百度云、阿里云 && 一半存在百度云，一半存在阿里云
							case 'dualStorage':
							case 'halfStorage':
								$GLOBALS['baiduClient']->deleteObject($baiduBucketName, $rs_del['objectName']);
								try{
									$aliOssClient->deleteObject($aliBucketName, $rs_del['objectName']);
								} catch (OssException $e) {
							        exceptionHandle($e);
							        return;
								}
								break;
							default:
								break;
						}
						$rs_delsql = Db::delete('object')->where('objectId=?',$delList[$i])->query();	
					}				
				}
				echo "<script>window.location='".SITE_URL."'</script>";			
			}
			//获取复制到Id 复制文件
			if(!empty($_GET['copyId']) && !empty($_GET['treeNodeId'])){
				//获得复制到目标地址
				$rs_target = Db::select('folderName','parentId')
							->from('foldertree')
							->where('folderId=?',$_GET['treeNodeId'])
							->limit(1)
							->fetch();
				$targetpath = array($rs_target['folderName']);
				while($rs_target['parentId']!=0){
					$rs_target = Db::select('folderName','parentId')
								->from('foldertree')
								->where('folderId=?',$rs_target['parentId'])
								->limit(1)
								->fetch();
					array_unshift($targetpath, $rs_target['folderName']);
				}
				$targetName = '';
				for($i = 1; $i < count($targetpath); $i++){
					$targetName = $targetName.$targetpath[$i].'/';
				}
				//复制列表
				$copyList = explode(',', $_GET['copyId']);
				for($i=0; $i<count($copyList)-1; $i++){
					$time = time();
					$rs_copy = Db::select('securityName','objectName','object.securityId','dataSecretEncrypted','objectSize','objectDate')
							->from('security','object')
							->join('object','security.securityId = object.securityId','RIGHT')
							->where('objectId=?',$copyList[$i])
							->limit(1)
							->fetch();
					
					$copyFileList = explode('/', $rs_copy['objectName']);
					$copyFileName = $copyFileList[count($copyFileList)-1];
					//复制文件夹
					if($copyFileName == ""){
						$rs_fileCopy = Db::select('securityName', 'objectName', 'objectId','object.securityId','dataSecretEncrypted','objectSize')
									->from('security','object')
									->join('object', 'security.securityId = object.securityId', 'RIGHT')
									->where('objectName like ?', '%'.$copyFileList[count($copyFileList)-2].'/%')
									->where('objectName like ?', $rs_copy['objectName'].'%')
									->fetchAll();
						//复制对应文件夹树
						//获得self 的folderId
						if(count($copyFileList) == 2){
							$rs_selfId = Db::select('folderId','folderName')
										->from('foldertree')
										->where('folderName=?',$copyFileList[count($copyFileList)-2])
										->where('parentId=?', 1)
										->fetch();
							$selfId = $rs_selfId['folderId'];
							$selfName = $rs_selfId['folderName'];
						}else{
							$rs_selfId = Db::select('folderId','folderName')
									->from('foldertree')
									->where('folderName=?',$copyFileList[count($copyFileList)-2])
									->fetchAll();
							foreach($rs_selfId as $row_selfId):
								$path = Controller_Treenode::get_path($row_selfId['folderId']);
								//选出所选择的文件夹
								//path 多出 0和全部文件   copyFileList 多出本身和空格
								if(count($path) == count($copyFileList)){
									$foldercount = 0;
									for($i=2, $j=0; $i<count($path); $i++,$j++){
										if($path[$i]['folderName'] == $copyFileList[$j]){
											$foldercount++;
										}
									}
									if($foldercount == count($path)-2){
										$selfId = $row_selfId['folderId'];
										$selfName = $row_selfId['folderName'];
										break;
									}
								}
							endforeach;
						}
						//复制失败的情况
						$selfChildren = Controller_Treenode::display_children($selfId);
						if($selfId != $_GET['treeNodeId']){
							foreach($selfChildren as $children):
								if($_GET['treeNodeId'] == $children['folderId']){
									echo "<html><meta charset='utf-8'><script>alert('复制文件夹失败，不能将文件夹复制到其子文件夹下！')</script>";
									echo "<script>window.location='".SITE_URL."'</script></html>
									";
									return;
								}
							endforeach;
						}else{
							echo "<html><meta charset='utf-8'><script>alert('复制文件夹失败，不能将文件夹复制到本身！')</script>";
							echo "<script>window.location='".SITE_URL."'</script></html>
							";
							return;
						}
						$rs_targetchild = Db::select('folderName')
										->from('foldertree')
										->where('parentId=?',$_GET['treeNodeId'])
										->fetchAll();
						$copyFolderExist = false;
						foreach($rs_targetchild as $row_targetchild):
							//文件夹名与所选择复制到文件夹的子文件夹名相同，编号
							if($selfName == $row_targetchild['folderName']){
								$copyFolderExist = true;
								$rs_ExistSeq = Db::select('folderName')
										->from('foldertree')
										->where('parentId=?', $_GET['treeNodeId'])
										->where('folderName like ?',$selfName.'(%)')
										->where('folderName not like?',$selfName.'(%)(%)')
										->fetchAll();
								if(!$rs_ExistSeq){
									$selfName = $selfName."(1)";
								}else{
									$seqArray = array();
									foreach($rs_ExistSeq as $row_ExistSeq):
										$leftHalfPos = strrpos($row_ExistSeq['folderName'],"(");
										$rightHalfPos = strrpos($row_ExistSeq['folderName'],")");
										$seq = substr($row_ExistSeq['folderName'], $leftHalfPos+1, $rightHalfPos-$leftHalfPos-1);
										array_push($seqArray, $seq);
									endforeach;
									//冒泡排序
									$cnt = count($seqArray);
									for($i = 0; $i < $cnt; $i++){
										for($j = 0; $j < $cnt - $i - 1; $j++){
											if($seqArray[$j] > $seqArray[$j+1]){
												$temp = $seqArray[$j];
												$seqArray[$j] = $seqArray[$j+1];
												$seqArray[$j+1] = $temp;
											}
										}
									}
									for($i = 1, $j = 0; $j < $cnt; $j++,$i++){
										if($i != $seqArray[$j])
											break;
									}
									$selfName = $selfName."(".$i.")";
								}
								break;
							}
						endforeach;
						$rs_folderSelfCopy = Db::insert('foldertree')->rows(array(
							'parentId' => $_GET['treeNodeId'],
							'folderName' => $selfName
						))->query();

						$rs_newselfId = Db::select('folderId')
										->from('foldertree')
										->where('parentId=?',$_GET['treeNodeId'])
										->where('folderName=?',$selfName)
										->limit(1)
										->fetch();
						Controller_Treenode::insert_children($selfId, $rs_newselfId['folderId']);
						//复制文件
						$folderprefix = $copyFileList[count($copyFileList)-2];
						foreach($rs_fileCopy as $row_fileCopy):
							$fileList = explode('/', $row_fileCopy['objectName']);
							$folderLength = strlen($rs_copy['objectName'])-strlen($folderprefix)-1;
							$folderName = substr($row_fileCopy['objectName'], $folderLength);
							if($copyFolderExist){
								$copyFolderExistList = explode('/', $folderName);
								$copyFolderExistList[0] = $selfName;
								$folderName = "";
								for($i = 0; $i < count($copyFolderExistList)-1; $i++){
									$folderName = $folderName.$copyFolderExistList[$i].'/';
								}
								$folderName = $folderName.$copyFolderExistList[count($copyFolderExistList)-1];
							}
							//文件夹
							if($fileList[count($fileList)-1] == ""){
								$newfile = $targetName.$folderName;
								$newfile = substr($newfile, 0, strlen($newfile)-1);
								try {
									$aliOssClient->createObjectDir($aliBucketName, $newfile);
								} catch (OssException $e) {
									exceptionHandle($e);
									return;
								}
								$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $newfile.'/', '');
								$rs_insert = Db::insert('object') ->rows(array(
									'userId' => $_SESSION['userId'],
									'securityId' => 3,
									'objectName' => $newfile.'/',
									'dataSecretEncrypted' => '',
									'objectSize' => 0,
									'objectDate' => time(),
								))->query();	
							}else{
								//获得文件名 $targetObject
								$targetObject = $targetName.$folderName;
								//选择复制的文件与当前文件的安全策略一致
								if($row_fileCopy['securityName'] == $securityName){
									switch ($row_fileCopy['securityName']) {
										//文件存在百度云
										case 'baiduCloud':
											$GLOBALS['baiduClient']->copyObject($baiduBucketName, $row_fileCopy['objectName'], $baiduBucketName, $targetObject);
											break;
										//存在阿里云
										case 'aliCloud':
											try{
												$options = array();
										        $aliOssClient->copyObject($aliBucketName, $row_fileCopy['objectName'], $aliBucketName, $targetObject, $options);
										    } catch(OssException $e) {
										        pexceptionHandle($e);
										        return;
										    }
											break;
										//分别存在百度云、阿里云 && 一半存在百度云，一半存在阿里云
										case 'dualStorage':
										case 'halfStorage':
											$GLOBALS['baiduClient']->copyObject($baiduBucketName, $row_fileCopy['objectName'], $baiduBucketName, $targetObject);
											try{
												$options = array();
										        $aliOssClient->copyObject($aliBucketName, $row_fileCopy['objectName'], $aliBucketName, $targetObject, $options);
										    } catch(OssException $e) {
										        pexceptionHandle($e);
										        return;
										    }
											break;
										default:
											break;
									}
									$row_fileCopyinsert = Db::insert('object')->rows(array(
											'userId' => $_SESSION['userId'],
											'securityId' => $row_fileCopy['securityId'],
											'objectName' => $targetObject,
											'dataSecretEncrypted' => $row_fileCopy['dataSecretEncrypted'],
											'objectSize' => $row_fileCopy['objectSize'],
											'objectDate' => $time,
										))->query();	
								}else{
									switch ($row_fileCopy['securityName']) {
										//文件存在百度云
										case 'baiduCloud':
											//下载object到本地变量
											$content = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $row_fileCopy['objectName']);
											break;
										
										//文件存在阿里云 && 文件分别存在百度云和阿里云
										case 'aliCloud':
										case 'dualStorage':
											// 下载object到本地变量
											$content = $aliOssClient->getObject($aliBucketName, $row_fileCopy['objectName']);
											break;

										//百度云、阿里云各存一半
										case 'halfStorage':
											// 下载object到本地变量
											$content_1 = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $row_fileCopy['objectName']);
											$content_2 = $aliOssClient->getObject($aliBucketName, $row_fileCopy['objectName']);
											$content = $content_1.$content_2;
											break;

										default:
											break;
									}
									switch($securityName){
										//存在百度云
										case 'baiduCloud':
											$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $targetObject, $content);
											$rs_baiduobject = Db::insert('object')->rows(array(
												'userId' => $_SESSION['userId'],
												'securityId' => $rs_security['securityId'],
												'objectName' => $targetObject,
												'dataSecretEncrypted' => $row_fileCopy['dataSecretEncrypted'],
												'objectSize' => $row_fileCopy['objectSize'],
												'objectDate' => $time,
											))->query();
											break;

										//存在阿里云
										case 'aliCloud':
											// 简单上传变量的内容到文件
											try{
												$aliOssClient->putObject($aliBucketName, $targetObject, $content);
											} catch (OssException $e) {
										        exceptionHandle($e);
										        return;
											}	
											//插入数据库
											$rs_aliobject = Db::insert('object') ->rows(array(
												'userId' => $_SESSION['userId'],
												'securityId' => $rs_security['securityId'],
												'objectName' => $targetObject,
												'dataSecretEncrypted' => $row_fileCopy['dataSecretEncrypted'],
												'objectSize' => $row_fileCopy['objectSize'],
												'objectDate' => $time,
											))->query();
											break;

										//分别存在百度云、阿里云
										case 'dualStorage':
											$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $targetObject, $content);
											try{
												$aliOssClient->putObject($aliBucketName, $targetObject, $content);
											} catch (OssException $e) {
										        exceptionHandle($e);
										        return;
											}	
											//插入数据库
											$rs_dualobject = Db::insert('object')->rows(array(
												'userId' => $_SESSION['userId'],
												'securityId' => $rs_security['securityId'],
												'objectName' => $targetObject,
												'dataSecretEncrypted' => $row_fileCopy['dataSecretEncrypted'],
												'objectSize' => $row_fileCopy['objectSize'],
												'objectDate' => $time,
											))->query();						
											break;

										//百度云、阿里云各存一半
										case 'halfStorage':
											$data_baidu = substr($content, 0, strlen($content)/2);
											$data_ali = substr($content, strlen($content)/2);
											$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $targetObject, $data_baidu);
											try{
												$aliOssClient->putObject($aliBucketName, $targetObject, $data_ali);
											} catch (OssException $e) {
										        exceptionHandle($e);
										        return;
											}	
											//插入数据库
											$rs_halfobject = Db::insert('object')->rows(array(
												'userId' => $_SESSION['userId'],
												'securityId' => $rs_security['securityId'],
												'objectName' => $targetObject,
												'dataSecretEncrypted' => $row_fileCopy['dataSecretEncrypted'],
												'objectSize' => $row_fileCopy['objectSize'],
												'objectDate' => $time,
											))->query();						
											break;

										default:
											break;
									}
								}
							}
						endforeach;
					}else{
					//复制文件
						//获得文件名 $targetObject
						if(count($copyFileList) == 1){
							$targetObject = $targetName.$rs_copy['objectName'];
						}else{
							$targetObject = $targetName.$copyFileName;
						}

						$rs_Exist = Db::select('securityName','objectName','objectId')
							->from('security','object')
							->join('object','security.securityId = object.securityId','RIGHT')
							->where('objectName=?',$targetObject)
							->fetch();
						//已存在文件，编号
						if($rs_Exist){
							$fileLastDotPos = strripos($targetObject, '.');
							$rs_ExistSeq = Db::select('securityName','objectName','objectId')
									->from('security','object')
									->join('object','security.securityId = object.securityId','RIGHT')
									->where('objectName like ?',substr($targetObject, 0, $fileLastDotPos).'(%).%')
									->where('objectName not like?',substr($targetObject, 0, $fileLastDotPos).'(%)(%).%')
									->fetchAll();
							if(!$rs_ExistSeq){
								$targetObject = substr($targetObject, 0, $fileLastDotPos)."(1)".substr($targetObject, $fileLastDotPos);
							}else{
								$seqArray = array();
								foreach($rs_ExistSeq as $row_ExistSeq):
									$leftHalfPos = strrpos($row_ExistSeq['objectName'],"(");
									$rightHalfPos = strrpos($row_ExistSeq['objectName'],")");
									$seq = substr($row_ExistSeq['objectName'], $leftHalfPos+1, $rightHalfPos-$leftHalfPos-1);
									array_push($seqArray, $seq);
								endforeach;
								//冒泡排序
								$cnt = count($seqArray);
								for($i = 0; $i < $cnt; $i++){
									for($j = 0; $j < $cnt - $i - 1; $j++){
										if($seqArray[$j] > $seqArray[$j+1]){
											$temp = $seqArray[$j];
											$seqArray[$j] = $seqArray[$j+1];
											$seqArray[$j+1] = $temp;
										}
									}
								}
								for($i = 1, $j = 0; $j < $cnt; $j++,$i++){
									if($i != $seqArray[$j])
										break;
								}
								$targetObject = substr($targetObject, 0, $fileLastDotPos)."(".$i.")".substr($targetObject, $fileLastDotPos);
							}
						}
						//选择复制的文件与当前文件的安全策略一致
						if($rs_copy['securityName'] == $securityName){
							switch ($rs_copy['securityName']) {
								//文件存在百度云
								case 'baiduCloud':
									$GLOBALS['baiduClient']->copyObject($baiduBucketName, $rs_copy['objectName'], $baiduBucketName, $targetObject);
									break;
								//存在阿里云
								case 'aliCloud':
									try{
										$options = array();
								        $aliOssClient->copyObject($aliBucketName, $rs_copy['objectName'], $aliBucketName, $targetObject, $options);
								    } catch(OssException $e) {
								        pexceptionHandle($e);
								        return;
								    }
									break;
								//分别存在百度云、阿里云 && 一半存在百度云，一半存在阿里云
								case 'dualStorage':
								case 'halfStorage':
									$GLOBALS['baiduClient']->copyObject($baiduBucketName, $rs_copy['objectName'], $baiduBucketName, $targetObject);
									try{
										$options = array();
								        $aliOssClient->copyObject($aliBucketName, $rs_copy['objectName'], $aliBucketName, $targetObject, $options);
								    } catch(OssException $e) {
								        pexceptionHandle($e);
								        return;
								    }
									break;
								default:
									break;
							}
							$rs_copyfile = Db::insert('object')->rows(array(
									'userId' => $_SESSION['userId'],
									'securityId' => $rs_copy['securityId'],
									'objectName' => $targetObject,
									'dataSecretEncrypted' => $rs_copy['dataSecretEncrypted'],
									'objectSize' => $rs_copy['objectSize'],
									'objectDate' => $time,
								))->query();	
						}else{
							switch ($rs_copy['securityName']) {
								//文件存在百度云
								case 'baiduCloud':
									//下载object到本地变量
									$content = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $rs_copy['objectName']);
									break;
								
								//文件存在阿里云 && 文件分别存在百度云和阿里云
								case 'aliCloud':
								case 'dualStorage':
									// 下载object到本地变量
									$content = $aliOssClient->getObject($aliBucketName, $rs_copy['objectName']);
									break;

								//百度云、阿里云各存一半
								case 'halfStorage':
									// 下载object到本地变量
									$content_1 = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $rs_copy['objectName']);
									$content_2 = $aliOssClient->getObject($aliBucketName, $rs_copy['objectName']);
									$content = $content_1.$content_2;
									break;

								default:
									break;
							}
							switch($securityName){
								//存在百度云
								case 'baiduCloud':
									$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $targetObject, $content);
									$rs_baiduobject = Db::insert('object')->rows(array(
										'userId' => $_SESSION['userId'],
										'securityId' => $rs_security['securityId'],
										'objectName' => $targetObject,
										'dataSecretEncrypted' => $rs_copy['dataSecretEncrypted'],
										'objectSize' => $rs_copy['objectSize'],
										'objectDate' => $time,
									))->query();
									break;

								//存在阿里云
								case 'aliCloud':
									// 简单上传变量的内容到文件
									try{
										$aliOssClient->putObject($aliBucketName, $targetObject, $content);
									} catch (OssException $e) {
								        exceptionHandle($e);
								        return;
									}	
									//插入数据库
									$rs_aliobject = Db::insert('object') ->rows(array(
										'userId' => $_SESSION['userId'],
										'securityId' => $rs_security['securityId'],
										'objectName' => $targetObject,
										'dataSecretEncrypted' => $rs_copy['dataSecretEncrypted'],
										'objectSize' => $rs_copy['objectSize'],
										'objectDate' => $time,
									))->query();
									break;

								//分别存在百度云、阿里云
								case 'dualStorage':
									$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $targetObject, $content);
									try{
										$aliOssClient->putObject($aliBucketName, $targetObject, $content);
									} catch (OssException $e) {
								        exceptionHandle($e);
								        return;
									}	
									//插入数据库
									$rs_dualobject = Db::insert('object')->rows(array(
										'userId' => $_SESSION['userId'],
										'securityId' => $rs_security['securityId'],
										'objectName' => $targetObject,
										'dataSecretEncrypted' => $rs_copy['dataSecretEncrypted'],
										'objectSize' => $rs_copy['objectSize'],
										'objectDate' => $time,
									))->query();					
									break;

								//百度云、阿里云各存一半
								case 'halfStorage':
									$data_baidu = substr($content, 0, strlen($content)/2);
									$data_ali = substr($content, strlen($content)/2);
									$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $targetObject, $data_baidu);
									try{
										$aliOssClient->putObject($aliBucketName, $targetObject, $data_ali);
									} catch (OssException $e) {
								        exceptionHandle($e);
								        return;
									}	
									//插入数据库
									$rs_halfobject = Db::insert('object')->rows(array(
										'userId' => $_SESSION['userId'],
										'securityId' => $rs_security['securityId'],
										'objectName' => $targetObject,
										'dataSecretEncrypted' => $rs_copy['dataSecretEncrypted'],
										'objectSize' => $rs_copy['objectSize'],
										'objectDate' => $time,
									))->query();						
									break;

								default:
									break;
							}
						}
					}    
				}
				echo "<script>window.location='".SITE_URL."'</script>";	
			}
			//获取移动到Id 移动文件
			if(!empty($_GET['removeId']) && !empty($_GET['treeNodeId'])){
				/*********************先复制**********************/
				//获得移动到目标地址
				$rs_target = Db::select('folderName','parentId')
							->from('foldertree')
							->where('folderId=?',$_GET['treeNodeId'])
							->limit(1)
							->fetch();
				$targetpath = array($rs_target['folderName']);
				while($rs_target['parentId']!=0){
					$rs_target = Db::select('folderName','parentId')
								->from('foldertree')
								->where('folderId=?',$rs_target['parentId'])
								->limit(1)
								->fetch();
					array_unshift($targetpath, $rs_target['folderName']);
				}
				$targetName = '';
				for($i = 1; $i < count($targetpath); $i++){
					$targetName = $targetName.$targetpath[$i].'/';
				}
				//移动列表
				$copyList = explode(',', $_GET['removeId']);
				for($i=0; $i<count($copyList)-1; $i++){
					$time = time();
					$rs_copy = Db::select('securityName','objectName','object.securityId','dataSecretEncrypted','objectSize','objectDate')
							->from('security','object')
							->join('object','security.securityId = object.securityId','RIGHT')
							->where('objectId=?',$copyList[$i])
							->limit(1)
							->fetch();
					
					$copyFileList = explode('/', $rs_copy['objectName']);
					$copyFileName = $copyFileList[count($copyFileList)-1];
					//复制文件夹
					if($copyFileName == ""){
						$rs_fileCopy = Db::select('securityName', 'objectName', 'objectId','object.securityId','dataSecretEncrypted','objectSize')
									->from('security','object')
									->join('object', 'security.securityId = object.securityId', 'RIGHT')
									->where('objectName like ?', '%'.$copyFileList[count($copyFileList)-2].'/%')
									->where('objectName like ?', $rs_copy['objectName'].'%')
									->fetchAll();
						//复制对应文件夹树
						//获得self 的folderId
						if(count($copyFileList) == 2){
							$rs_selfId = Db::select('folderId','folderName')
										->from('foldertree')
										->where('folderName=?',$copyFileList[count($copyFileList)-2])
										->where('parentId=?', 1)
										->fetch();
							$selfId = $rs_selfId['folderId'];
							$selfName = $rs_selfId['folderName'];
						}else{
							$rs_selfId = Db::select('folderId','folderName')
									->from('foldertree')
									->where('folderName=?',$copyFileList[count($copyFileList)-2])
									->fetchAll();
							foreach($rs_selfId as $row_selfId):
								$path = Controller_Treenode::get_path($row_selfId['folderId']);
								//选出所选择的文件夹
								//path 多出 0和全部文件   copyFileList 多出本身和空格
								if(count($path) == count($copyFileList)){
									$foldercount = 0;
									for($i=2, $j=0; $i<count($path); $i++,$j++){
										if($path[$i]['folderName'] == $copyFileList[$j]){
											$foldercount++;
										}
									}
									if($foldercount == count($path)-2){
										$selfId = $row_selfId['folderId'];
										$selfName = $row_selfId['folderName'];
										break;
									}
								}
							endforeach;
						}
						//复制失败的情况
						$selfChildren = Controller_Treenode::display_children($selfId);
						if($selfId != $_GET['treeNodeId']){
							foreach($selfChildren as $children):
								if($_GET['treeNodeId'] == $children['folderId']){
									echo "<html><meta charset='utf-8'><script>alert('移动文件夹失败，不能将文件夹移动到其子文件夹下！')</script>";
									echo "<script>window.location='".SITE_URL."'</script></html>
									";
									return;
								}
							endforeach;
						}else{
							echo "<html><meta charset='utf-8'><script>alert('移动文件夹失败，不能将文件夹移动到本身！')</script>";
							echo "<script>window.location='".SITE_URL."'</script></html>
							";
							return;
						}
						$rs_targetchild = Db::select('folderName')
										->from('foldertree')
										->where('parentId=?',$_GET['treeNodeId'])
										->fetchAll();
						$copyFolderExist = false;
						foreach($rs_targetchild as $row_targetchild):
							//文件夹名与所选择复制到文件夹的子文件夹名相同，编号
							if($selfName == $row_targetchild['folderName']){
								$copyFolderExist = true;
								$rs_ExistSeq = Db::select('folderName')
										->from('foldertree')
										->where('parentId=?', $_GET['treeNodeId'])
										->where('folderName like ?',$selfName.'(%)')
										->where('folderName not like?',$selfName.'(%)(%)')
										->fetchAll();
								if(!$rs_ExistSeq){
									$selfName = $selfName."(1)";
								}else{
									$seqArray = array();
									foreach($rs_ExistSeq as $row_ExistSeq):
										$leftHalfPos = strrpos($row_ExistSeq['folderName'],"(");
										$rightHalfPos = strrpos($row_ExistSeq['folderName'],")");
										$seq = substr($row_ExistSeq['folderName'], $leftHalfPos+1, $rightHalfPos-$leftHalfPos-1);
										array_push($seqArray, $seq);
									endforeach;
									//冒泡排序
									$cnt = count($seqArray);
									for($i = 0; $i < $cnt; $i++){
										for($j = 0; $j < $cnt - $i - 1; $j++){
											if($seqArray[$j] > $seqArray[$j+1]){
												$temp = $seqArray[$j];
												$seqArray[$j] = $seqArray[$j+1];
												$seqArray[$j+1] = $temp;
											}
										}
									}
									for($i = 1, $j = 0; $j < $cnt; $j++,$i++){
										if($i != $seqArray[$j])
											break;
									}
									$selfName = $selfName."(".$i.")";
								}
								break;
							}
						endforeach;
						$rs_folderSelfCopy = Db::insert('foldertree')->rows(array(
							'parentId' => $_GET['treeNodeId'],
							'folderName' => $selfName
						))->query();

						$rs_newselfId = Db::select('folderId')
										->from('foldertree')
										->where('parentId=?',$_GET['treeNodeId'])
										->where('folderName=?',$selfName)
										->limit(1)
										->fetch();
						Controller_Treenode::insert_children($selfId, $rs_newselfId['folderId']);
						//复制文件
						$folderprefix = $copyFileList[count($copyFileList)-2];
						foreach($rs_fileCopy as $row_fileCopy):
							$fileList = explode('/', $row_fileCopy['objectName']);
							$folderLength = strlen($rs_copy['objectName'])-strlen($folderprefix)-1;
							$folderName = substr($row_fileCopy['objectName'], $folderLength);
							if($copyFolderExist){
								$copyFolderExistList = explode('/', $folderName);
								$copyFolderExistList[0] = $selfName;
								$folderName = "";
								for($i = 0; $i < count($copyFolderExistList)-1; $i++){
									$folderName = $folderName.$copyFolderExistList[$i].'/';
								}
								$folderName = $folderName.$copyFolderExistList[count($copyFolderExistList)-1];
							}
							//文件夹
							if($fileList[count($fileList)-1] == ""){
								$newfile = $targetName.$folderName;
								$newfile = substr($newfile, 0, strlen($newfile)-1);
								try {
									$aliOssClient->createObjectDir($aliBucketName, $newfile);
								} catch (OssException $e) {
									exceptionHandle($e);
									return;
								}
								$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $newfile.'/', '');
								$rs_insert = Db::insert('object') ->rows(array(
									'userId' => $_SESSION['userId'],
									'securityId' => 3,
									'objectName' => $newfile.'/',
									'dataSecretEncrypted' => '',
									'objectSize' => 0,
									'objectDate' => time(),
								))->query();	
							}else{
								//获得文件名 $targetObject
								$targetObject = $targetName.$folderName;
								//选择复制的文件与当前文件的安全策略一致
								if($row_fileCopy['securityName'] == $securityName){
									switch ($row_fileCopy['securityName']) {
										//文件存在百度云
										case 'baiduCloud':
											$GLOBALS['baiduClient']->copyObject($baiduBucketName, $row_fileCopy['objectName'], $baiduBucketName, $targetObject);
											break;
										//存在阿里云
										case 'aliCloud':
											try{
												$options = array();
										        $aliOssClient->copyObject($aliBucketName, $row_fileCopy['objectName'], $aliBucketName, $targetObject, $options);
										    } catch(OssException $e) {
										        pexceptionHandle($e);
										        return;
										    }
											break;
										//分别存在百度云、阿里云 && 一半存在百度云，一半存在阿里云
										case 'dualStorage':
										case 'halfStorage':
											$GLOBALS['baiduClient']->copyObject($baiduBucketName, $row_fileCopy['objectName'], $baiduBucketName, $targetObject);
											try{
												$options = array();
										        $aliOssClient->copyObject($aliBucketName, $row_fileCopy['objectName'], $aliBucketName, $targetObject, $options);
										    } catch(OssException $e) {
										        pexceptionHandle($e);
										        return;
										    }
											break;
										default:
											break;
									}
									$row_fileCopyinsert = Db::insert('object')->rows(array(
											'userId' => $_SESSION['userId'],
											'securityId' => $row_fileCopy['securityId'],
											'objectName' => $targetObject,
											'dataSecretEncrypted' => $row_fileCopy['dataSecretEncrypted'],
											'objectSize' => $row_fileCopy['objectSize'],
											'objectDate' => $time,
										))->query();	
								}else{
									switch ($row_fileCopy['securityName']) {
										//文件存在百度云
										case 'baiduCloud':
											//下载object到本地变量
											$content = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $row_fileCopy['objectName']);
											break;
										
										//文件存在阿里云 && 文件分别存在百度云和阿里云
										case 'aliCloud':
										case 'dualStorage':
											// 下载object到本地变量
											$content = $aliOssClient->getObject($aliBucketName, $row_fileCopy['objectName']);
											break;

										//百度云、阿里云各存一半
										case 'halfStorage':
											// 下载object到本地变量
											$content_1 = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $row_fileCopy['objectName']);
											$content_2 = $aliOssClient->getObject($aliBucketName, $row_fileCopy['objectName']);
											$content = $content_1.$content_2;
											break;

										default:
											break;
									}
									switch($securityName){
										//存在百度云
										case 'baiduCloud':
											$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $targetObject, $content);
											$rs_baiduobject = Db::insert('object')->rows(array(
												'userId' => $_SESSION['userId'],
												'securityId' => $rs_security['securityId'],
												'objectName' => $targetObject,
												'dataSecretEncrypted' => $row_fileCopy['dataSecretEncrypted'],
												'objectSize' => $row_fileCopy['objectSize'],
												'objectDate' => $time,
											))->query();
											break;

										//存在阿里云
										case 'aliCloud':
											// 简单上传变量的内容到文件
											try{
												$aliOssClient->putObject($aliBucketName, $targetObject, $content);
											} catch (OssException $e) {
										        exceptionHandle($e);
										        return;
											}	
											//插入数据库
											$rs_aliobject = Db::insert('object') ->rows(array(
												'userId' => $_SESSION['userId'],
												'securityId' => $rs_security['securityId'],
												'objectName' => $targetObject,
												'dataSecretEncrypted' => $row_fileCopy['dataSecretEncrypted'],
												'objectSize' => $row_fileCopy['objectSize'],
												'objectDate' => $time,
											))->query();
											break;

										//分别存在百度云、阿里云
										case 'dualStorage':
											$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $targetObject, $content);
											try{
												$aliOssClient->putObject($aliBucketName, $targetObject, $content);
											} catch (OssException $e) {
										        exceptionHandle($e);
										        return;
											}	
											//插入数据库
											$rs_dualobject = Db::insert('object')->rows(array(
												'userId' => $_SESSION['userId'],
												'securityId' => $rs_security['securityId'],
												'objectName' => $targetObject,
												'dataSecretEncrypted' => $row_fileCopy['dataSecretEncrypted'],
												'objectSize' => $row_fileCopy['objectSize'],
												'objectDate' => $time,
											))->query();						
											break;

										//百度云、阿里云各存一半
										case 'halfStorage':
											$data_baidu = substr($content, 0, strlen($content)/2);
											$data_ali = substr($content, strlen($content)/2);
											$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $targetObject, $data_baidu);
											try{
												$aliOssClient->putObject($aliBucketName, $targetObject, $data_ali);
											} catch (OssException $e) {
										        exceptionHandle($e);
										        return;
											}	
											//插入数据库
											$rs_halfobject = Db::insert('object')->rows(array(
												'userId' => $_SESSION['userId'],
												'securityId' => $rs_security['securityId'],
												'objectName' => $targetObject,
												'dataSecretEncrypted' => $row_fileCopy['dataSecretEncrypted'],
												'objectSize' => $row_fileCopy['objectSize'],
												'objectDate' => $time,
											))->query();						
											break;

										default:
											break;
									}
								}
							}
						endforeach;
					}else{
					//复制文件
						//获得文件名 $targetObject
						if(count($copyFileList) == 1){
							$targetObject = $targetName.$rs_copy['objectName'];
						}else{
							$targetObject = $targetName.$copyFileName;
						}

						$rs_Exist = Db::select('securityName','objectName','objectId')
							->from('security','object')
							->join('object','security.securityId = object.securityId','RIGHT')
							->where('objectName=?',$targetObject)
							->fetch();
						//已存在文件，编号
						if($rs_Exist){
							$fileLastDotPos = strripos($targetObject, '.');
							$rs_ExistSeq = Db::select('securityName','objectName','objectId')
									->from('security','object')
									->join('object','security.securityId = object.securityId','RIGHT')
									->where('objectName like ?',substr($targetObject, 0, $fileLastDotPos).'(%).%')
									->where('objectName not like?',substr($targetObject, 0, $fileLastDotPos).'(%)(%).%')
									->fetchAll();
							if(!$rs_ExistSeq){
								$targetObject = substr($targetObject, 0, $fileLastDotPos)."(1)".substr($targetObject, $fileLastDotPos);
							}else{
								$seqArray = array();
								foreach($rs_ExistSeq as $row_ExistSeq):
									$leftHalfPos = strrpos($row_ExistSeq['objectName'],"(");
									$rightHalfPos = strrpos($row_ExistSeq['objectName'],")");
									$seq = substr($row_ExistSeq['objectName'], $leftHalfPos+1, $rightHalfPos-$leftHalfPos-1);
									array_push($seqArray, $seq);
								endforeach;
								//冒泡排序
								$cnt = count($seqArray);
								for($i = 0; $i < $cnt; $i++){
									for($j = 0; $j < $cnt - $i - 1; $j++){
										if($seqArray[$j] > $seqArray[$j+1]){
											$temp = $seqArray[$j];
											$seqArray[$j] = $seqArray[$j+1];
											$seqArray[$j+1] = $temp;
										}
									}
								}
								for($i = 1, $j = 0; $j < $cnt; $j++,$i++){
									if($i != $seqArray[$j])
										break;
								}
								$targetObject = substr($targetObject, 0, $fileLastDotPos)."(".$i.")".substr($targetObject, $fileLastDotPos);
							}
						}
						//选择复制的文件与当前文件的安全策略一致
						if($rs_copy['securityName'] == $securityName){
							switch ($rs_copy['securityName']) {
								//文件存在百度云
								case 'baiduCloud':
									$GLOBALS['baiduClient']->copyObject($baiduBucketName, $rs_copy['objectName'], $baiduBucketName, $targetObject);
									break;
								//存在阿里云
								case 'aliCloud':
									try{
										$options = array();
								        $aliOssClient->copyObject($aliBucketName, $rs_copy['objectName'], $aliBucketName, $targetObject, $options);
								    } catch(OssException $e) {
								        pexceptionHandle($e);
								        return;
								    }
									break;
								//分别存在百度云、阿里云 && 一半存在百度云，一半存在阿里云
								case 'dualStorage':
								case 'halfStorage':
									$GLOBALS['baiduClient']->copyObject($baiduBucketName, $rs_copy['objectName'], $baiduBucketName, $targetObject);
									try{
										$options = array();
								        $aliOssClient->copyObject($aliBucketName, $rs_copy['objectName'], $aliBucketName, $targetObject, $options);
								    } catch(OssException $e) {
								        pexceptionHandle($e);
								        return;
								    }
									break;
								default:
									break;
							}
							$rs_copyfile = Db::insert('object')->rows(array(
									'userId' => $_SESSION['userId'],
									'securityId' => $rs_copy['securityId'],
									'objectName' => $targetObject,
									'dataSecretEncrypted' => $rs_copy['dataSecretEncrypted'],
									'objectSize' => $rs_copy['objectSize'],
									'objectDate' => $time,
								))->query();	
						}else{
							switch ($rs_copy['securityName']) {
								//文件存在百度云
								case 'baiduCloud':
									//下载object到本地变量
									$content = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $rs_copy['objectName']);
									break;
								
								//文件存在阿里云 && 文件分别存在百度云和阿里云
								case 'aliCloud':
								case 'dualStorage':
									// 下载object到本地变量
									$content = $aliOssClient->getObject($aliBucketName, $rs_copy['objectName']);
									break;

								//百度云、阿里云各存一半
								case 'halfStorage':
									// 下载object到本地变量
									$content_1 = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $rs_copy['objectName']);
									$content_2 = $aliOssClient->getObject($aliBucketName, $rs_copy['objectName']);
									$content = $content_1.$content_2;
									break;

								default:
									break;
							}
							switch($securityName){
								//存在百度云
								case 'baiduCloud':
									$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $targetObject, $content);
									$rs_baiduobject = Db::insert('object')->rows(array(
										'userId' => $_SESSION['userId'],
										'securityId' => $rs_security['securityId'],
										'objectName' => $targetObject,
										'dataSecretEncrypted' => $rs_copy['dataSecretEncrypted'],
										'objectSize' => $rs_copy['objectSize'],
										'objectDate' => $time,
									))->query();
									break;

								//存在阿里云
								case 'aliCloud':
									// 简单上传变量的内容到文件
									try{
										$aliOssClient->putObject($aliBucketName, $targetObject, $content);
									} catch (OssException $e) {
								        exceptionHandle($e);
								        return;
									}	
									//插入数据库
									$rs_aliobject = Db::insert('object') ->rows(array(
										'userId' => $_SESSION['userId'],
										'securityId' => $rs_security['securityId'],
										'objectName' => $targetObject,
										'dataSecretEncrypted' => $rs_copy['dataSecretEncrypted'],
										'objectSize' => $rs_copy['objectSize'],
										'objectDate' => $time,
									))->query();
									break;

								//分别存在百度云、阿里云
								case 'dualStorage':
									$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $targetObject, $content);
									try{
										$aliOssClient->putObject($aliBucketName, $targetObject, $content);
									} catch (OssException $e) {
								        exceptionHandle($e);
								        return;
									}	
									//插入数据库
									$rs_dualobject = Db::insert('object')->rows(array(
										'userId' => $_SESSION['userId'],
										'securityId' => $rs_security['securityId'],
										'objectName' => $targetObject,
										'dataSecretEncrypted' => $rs_copy['dataSecretEncrypted'],
										'objectSize' => $rs_copy['objectSize'],
										'objectDate' => $time,
									))->query();					
									break;

								//百度云、阿里云各存一半
								case 'halfStorage':
									$data_baidu = substr($content, 0, strlen($content)/2);
									$data_ali = substr($content, strlen($content)/2);
									$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $targetObject, $data_baidu);
									try{
										$aliOssClient->putObject($aliBucketName, $targetObject, $data_ali);
									} catch (OssException $e) {
								        exceptionHandle($e);
								        return;
									}	
									//插入数据库
									$rs_halfobject = Db::insert('object')->rows(array(
										'userId' => $_SESSION['userId'],
										'securityId' => $rs_security['securityId'],
										'objectName' => $targetObject,
										'dataSecretEncrypted' => $rs_copy['dataSecretEncrypted'],
										'objectSize' => $rs_copy['objectSize'],
										'objectDate' => $time,
									))->query();						
									break;

								default:
									break;
							}
						}
					}    		
				}
				/*********************再删除原文件**********************/
				for($i=0; $i<count($copyList)-1; $i++){
					$rs_del = Db::select('securityName','objectName')
							->from('security','object')
							->join('object','security.securityId = object.securityId','RIGHT')
							->where('objectId=?',$copyList[$i])
							->limit(1)
							->fetch();
					$delObjectList = explode('/', $rs_del['objectName']);
					$delprefix = $delObjectList[count($delObjectList)-1];
					//删除文件夹
					if($delprefix == ""){
						$rs_fileDel = Db::select('securityName', 'objectName', 'objectId')
									->from('security','object')
									->join('object', 'security.securityId = object.securityId', 'RIGHT')
									->where('objectName like ?', '%'.$delObjectList[count($delObjectList)-2].'/%')
									->where('objectName like ?', $rs_del['objectName'].'%')
									->fetchAll();
						//删除对应文件夹树
						//第一级文件夹
						if(count($delObjectList) == 2){
							$rs_selfId = Db::select('folderId')
										->from('foldertree')
										->where('folderName=?',$delObjectList[count($delObjectList)-2])
										->where('parentId=?', 1)
										->fetch();
							$selfId = $rs_selfId['folderId'];
						}else{
							$rs_selfId = Db::select('folderId')
									->from('foldertree')
									->where('folderName=?',$delObjectList[count($delObjectList)-2])
									->fetchAll();
							foreach($rs_selfId as $row_selfId):
								$path = Controller_Treenode::get_path($row_selfId['folderId']);
								//选出所选择的文件夹
								//path 多出 0和全部文件   delObjectList 多出本身和空格
								if(count($path) == count($delObjectList)){
									$foldercount = 0;
									for($i=2, $j=0; $i<count($path); $i++,$j++){
										if($path[$i]['folderName'] == $delObjectList[$j]){
											$foldercount++;
										}
									}
									if($foldercount == count($path)-2){
										$selfId = $row_selfId['folderId'];
										break;
									}
								}
							endforeach;
						}
						Controller_Treenode::delete_children($selfId);
						$rs_folderDel = Db::delete('foldertree')->where('parentId=?',$selfId)->query();
						$rs_folderSelfDel = Db::delete('foldertree')->where('folderId=?',$selfId)->query();
						foreach($rs_fileDel as $row_fileDel):
							switch ($row_fileDel['securityName']) {
								//文件存在百度云
								case 'baiduCloud':
									$GLOBALS['baiduClient']->deleteObject($baiduBucketName, $row_fileDel['objectName']);
									break;
								//存在阿里云
								case 'aliCloud':
									try{
										$aliOssClient->deleteObject($aliBucketName, $row_fileDel['objectName']);
									} catch (OssException $e) {
								        exceptionHandle($e);
								        return;
									}
									break;
								//分别存在百度云、阿里云 && 一半存在百度云，一半存在阿里云
								case 'dualStorage':
								case 'halfStorage':
									$GLOBALS['baiduClient']->deleteObject($baiduBucketName, $row_fileDel['objectName']);
									try{
										$aliOssClient->deleteObject($aliBucketName, $row_fileDel['objectName']);
									} catch (OssException $e) {
								        exceptionHandle($e);
								        return;
									}
									break;
								default:
									break;
							}
							$rs_filedelsql = Db::delete('object')->where('objectId=?',$row_fileDel['objectId'])->query();
						endforeach;
					}else{
						switch ($rs_del['securityName']) {
							//文件存在百度云
							case 'baiduCloud':
								$GLOBALS['baiduClient']->deleteObject($baiduBucketName, $rs_del['objectName']);
								break;
							//存在阿里云
							case 'aliCloud':
								try{
									$aliOssClient->deleteObject($aliBucketName, $rs_del['objectName']);
								} catch (OssException $e) {
							        exceptionHandle($e);
							        return;
								}
								break;
							//分别存在百度云、阿里云 && 一半存在百度云，一半存在阿里云
							case 'dualStorage':
							case 'halfStorage':
								$GLOBALS['baiduClient']->deleteObject($baiduBucketName, $rs_del['objectName']);
								try{
									$aliOssClient->deleteObject($aliBucketName, $rs_del['objectName']);
								} catch (OssException $e) {
							        exceptionHandle($e);
							        return;
								}
								break;
							default:
								break;
						}
						$rs_delsql = Db::delete('object')->where('objectId=?',$copyList[$i])->query();	
					}				
				}
				echo "<script>window.location='".SITE_URL."'</script>";	
			}
			//获取重命名Id 重命名
			if(!empty($_POST['checkedId'])){
				//重命名文件Id
				$objectId = $_POST['checkedId'];
				$renamefile = "renamefile".$objectId;
				//重命名文件名
				$renamefile = $_POST[$renamefile];
				if(!empty($_GET['folderName'])){
					$renamefile = $_GET['folderName'].$renamefile;
				}
				$renameFileList = explode('/', $renamefile);
				//当前时间
				$time = time();
				$rs_copy = Db::select('securityName','objectName','object.securityId','dataSecretEncrypted','objectSize','objectDate')
						->from('security','object')
						->join('object','security.securityId = object.securityId','RIGHT')
						->where('objectId=?',$objectId)
						->limit(1)
						->fetch();
				
				$copyFileList = explode('/', $rs_copy['objectName']);
				$copyFileName = $copyFileList[count($copyFileList)-1];
				// //复制文件夹
				if($copyFileName == ""){
					$rs_fileCopy = Db::select('securityName', 'objectName', 'objectId','object.securityId','dataSecretEncrypted','objectSize')
								->from('security','object')
								->join('object', 'security.securityId = object.securityId', 'RIGHT')
								->where('objectName like ?', '%'.$copyFileList[count($copyFileList)-2].'/%')
								->where('objectName like ?', $rs_copy['objectName'].'%')
								->fetchAll();

					//重命名文件的最后文件名 && 路径
					$renameFileName = $renameFileList[count($renameFileList)-2];
					$targetName = "";
					for($i = 0; $i < count($renameFileList)-2; $i++){
						$targetName = $targetName.$renameFileList[$i].'/';
					}

					//复制对应文件夹树
					//获得self 的folderId
					if(count($copyFileList) == 2){
						$rs_selfId = Db::select('folderId','folderName')
									->from('foldertree')
									->where('folderName=?',$copyFileList[count($copyFileList)-2])
									->where('parentId=?', 1)
									->fetch();
						$selfId = $rs_selfId['folderId'];
						$selfName = $rs_selfId['folderName'];
					}else{
						$rs_selfId = Db::select('folderId','folderName')
								->from('foldertree')
								->where('folderName=?',$copyFileList[count($copyFileList)-2])
								->fetchAll();
						foreach($rs_selfId as $row_selfId):
							$path = Controller_Treenode::get_path($row_selfId['folderId']);
							//选出所选择的文件夹
							//path 多出 0和全部文件   copyFileList 多出本身和空格
							if(count($path) == count($copyFileList)){
								$foldercount = 0;
								for($i=2, $j=0; $i<count($path); $i++,$j++){
									if($path[$i]['folderName'] == $copyFileList[$j]){
										$foldercount++;
									}
								}
								if($foldercount == count($path)-2){
									$selfId = $row_selfId['folderId'];
									$selfName = $row_selfId['folderName'];
									break;
								}
							}
						endforeach;
					}

					$renameparent = Db::select('folderName','parentId')
									->from('foldertree')
									->where('folderId=?',$selfId)
									->limit(1)
									->fetch();
					$rs_targetchild = Db::select('folderName')
									->from('foldertree')
									->where('parentId=?',$renameparent['parentId'])
									->fetchAll();
					$copyFolderExist = false;
					foreach($rs_targetchild as $row_targetchild):
						//文件夹名与所选择复制到文件夹的子文件夹名相同，编号
						if($renameFileName == $row_targetchild['folderName']){
							$copyFolderExist = true;
							$rs_ExistSeq = Db::select('folderName')
									->from('foldertree')
									->where('parentId=?', $renameparent['parentId'])
									->where('folderName like ?',$renameFileName.'(%)')
									->where('folderName not like?',$renameFileName.'(%)(%)')
									->fetchAll();
							if(!$rs_ExistSeq){
								$renameFileName = $renameFileName."(1)";
							}else{
								$seqArray = array();
								foreach($rs_ExistSeq as $row_ExistSeq):
									$leftHalfPos = strrpos($row_ExistSeq['folderName'],"(");
									$rightHalfPos = strrpos($row_ExistSeq['folderName'],")");
									$seq = substr($row_ExistSeq['folderName'], $leftHalfPos+1, $rightHalfPos-$leftHalfPos-1);
									array_push($seqArray, $seq);
								endforeach;
								//冒泡排序
								$cnt = count($seqArray);
								for($i = 0; $i < $cnt; $i++){
									for($j = 0; $j < $cnt - $i - 1; $j++){
										if($seqArray[$j] > $seqArray[$j+1]){
											$temp = $seqArray[$j];
											$seqArray[$j] = $seqArray[$j+1];
											$seqArray[$j+1] = $temp;
										}
									}
								}
								for($i = 1, $j = 0; $j < $cnt; $j++,$i++){
									if($i != $seqArray[$j])
										break;
								}
								$renameFileName = $renameFileName."(".$i.")";
							}
							break;
						}
					endforeach;
					$rs_folderSelfCopy = Db::insert('foldertree')->rows(array(
						'parentId' => $renameparent['parentId'],
						'folderName' => $renameFileName
					))->query();

					$rs_newselfId = Db::select('folderId')
									->from('foldertree')
									->where('parentId=?',$renameparent['parentId'])
									->where('folderName=?',$renameFileName)
									->limit(1)
									->fetch();
					Controller_Treenode::insert_children($selfId, $rs_newselfId['folderId']);
					//复制文件
					$folderprefix = $copyFileList[count($copyFileList)-2];
					foreach($rs_fileCopy as $row_fileCopy):
						$fileList = explode('/', $row_fileCopy['objectName']);
						$folderLength = strlen($rs_copy['objectName'])-strlen($folderprefix)-1;
						$folderName = substr($row_fileCopy['objectName'], $folderLength);

						//将名字替换掉
						$copyFolderExistList = explode('/', $folderName);
						$copyFolderExistList[0] = $renameFileName;
						$folderName = "";
						for($i = 0; $i < count($copyFolderExistList)-1; $i++){
							$folderName = $folderName.$copyFolderExistList[$i].'/';
						}
						$folderName = $folderName.$copyFolderExistList[count($copyFolderExistList)-1];
						//文件夹
						if($fileList[count($fileList)-1] == ""){
							$newfile = $targetName.$folderName;
							$newfile = substr($newfile, 0, strlen($newfile)-1);
							try {
								$aliOssClient->createObjectDir($aliBucketName, $newfile);
							} catch (OssException $e) {
								exceptionHandle($e);
								return;
							}
							$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $newfile.'/', '');
							$rs_insert = Db::insert('object') ->rows(array(
								'userId' => $_SESSION['userId'],
								'securityId' => 3,
								'objectName' => $newfile.'/',
								'dataSecretEncrypted' => '',
								'objectSize' => 0,
								'objectDate' => time(),
							))->query();	
						}else{
							//获得文件名 $targetObject
							$targetObject = $targetName.$folderName;
							//选择复制的文件与当前文件的安全策略一致
							if($row_fileCopy['securityName'] == $securityName){
								switch ($row_fileCopy['securityName']) {
									//文件存在百度云
									case 'baiduCloud':
										$GLOBALS['baiduClient']->copyObject($baiduBucketName, $row_fileCopy['objectName'], $baiduBucketName, $targetObject);
										break;
									//存在阿里云
									case 'aliCloud':
										try{
											$options = array();
									        $aliOssClient->copyObject($aliBucketName, $row_fileCopy['objectName'], $aliBucketName, $targetObject, $options);
									    } catch(OssException $e) {
									        pexceptionHandle($e);
									        return;
									    }
										break;
									//分别存在百度云、阿里云 && 一半存在百度云，一半存在阿里云
									case 'dualStorage':
									case 'halfStorage':
										$GLOBALS['baiduClient']->copyObject($baiduBucketName, $row_fileCopy['objectName'], $baiduBucketName, $targetObject);
										try{
											$options = array();
									        $aliOssClient->copyObject($aliBucketName, $row_fileCopy['objectName'], $aliBucketName, $targetObject, $options);
									    } catch(OssException $e) {
									        pexceptionHandle($e);
									        return;
									    }
										break;
									default:
										break;
								}
								$row_fileCopyinsert = Db::insert('object')->rows(array(
										'userId' => $_SESSION['userId'],
										'securityId' => $row_fileCopy['securityId'],
										'objectName' => $targetObject,
										'dataSecretEncrypted' => $row_fileCopy['dataSecretEncrypted'],
										'objectSize' => $row_fileCopy['objectSize'],
										'objectDate' => $time,
									))->query();	
							}else{
								switch ($row_fileCopy['securityName']) {
									//文件存在百度云
									case 'baiduCloud':
										//下载object到本地变量
										$content = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $row_fileCopy['objectName']);
										break;
									
									//文件存在阿里云 && 文件分别存在百度云和阿里云
									case 'aliCloud':
									case 'dualStorage':
										// 下载object到本地变量
										$content = $aliOssClient->getObject($aliBucketName, $row_fileCopy['objectName']);
										break;

									//百度云、阿里云各存一半
									case 'halfStorage':
										// 下载object到本地变量
										$content_1 = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $row_fileCopy['objectName']);
										$content_2 = $aliOssClient->getObject($aliBucketName, $row_fileCopy['objectName']);
										$content = $content_1.$content_2;
										break;

									default:
										break;
								}
								switch($securityName){
									//存在百度云
									case 'baiduCloud':
										$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $targetObject, $content);
										$rs_baiduobject = Db::insert('object')->rows(array(
											'userId' => $_SESSION['userId'],
											'securityId' => $rs_security['securityId'],
											'objectName' => $targetObject,
											'dataSecretEncrypted' => $row_fileCopy['dataSecretEncrypted'],
											'objectSize' => $row_fileCopy['objectSize'],
											'objectDate' => $time,
										))->query();
										break;

									//存在阿里云
									case 'aliCloud':
										// 简单上传变量的内容到文件
										try{
											$aliOssClient->putObject($aliBucketName, $targetObject, $content);
										} catch (OssException $e) {
									        exceptionHandle($e);
									        return;
										}	
										//插入数据库
										$rs_aliobject = Db::insert('object') ->rows(array(
											'userId' => $_SESSION['userId'],
											'securityId' => $rs_security['securityId'],
											'objectName' => $targetObject,
											'dataSecretEncrypted' => $row_fileCopy['dataSecretEncrypted'],
											'objectSize' => $row_fileCopy['objectSize'],
											'objectDate' => $time,
										))->query();
										break;

									//分别存在百度云、阿里云
									case 'dualStorage':
										$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $targetObject, $content);
										try{
											$aliOssClient->putObject($aliBucketName, $targetObject, $content);
										} catch (OssException $e) {
									        exceptionHandle($e);
									        return;
										}	
										//插入数据库
										$rs_dualobject = Db::insert('object')->rows(array(
											'userId' => $_SESSION['userId'],
											'securityId' => $rs_security['securityId'],
											'objectName' => $targetObject,
											'dataSecretEncrypted' => $row_fileCopy['dataSecretEncrypted'],
											'objectSize' => $row_fileCopy['objectSize'],
											'objectDate' => $time,
										))->query();						
										break;

									//百度云、阿里云各存一半
									case 'halfStorage':
										$data_baidu = substr($content, 0, strlen($content)/2);
										$data_ali = substr($content, strlen($content)/2);
										$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $targetObject, $data_baidu);
										try{
											$aliOssClient->putObject($aliBucketName, $targetObject, $data_ali);
										} catch (OssException $e) {
									        exceptionHandle($e);
									        return;
										}	
										//插入数据库
										$rs_halfobject = Db::insert('object')->rows(array(
											'userId' => $_SESSION['userId'],
											'securityId' => $rs_security['securityId'],
											'objectName' => $targetObject,
											'dataSecretEncrypted' => $row_fileCopy['dataSecretEncrypted'],
											'objectSize' => $row_fileCopy['objectSize'],
											'objectDate' => $time,
										))->query();						
										break;

									default:
										break;
								}
							}
						}
					endforeach;
				}else{
				//复制文件
					//获得文件名 $targetObject
					$targetObject = $renamefile;

					$rs_Exist = Db::select('securityName','objectName','objectId')
						->from('security','object')
						->join('object','security.securityId = object.securityId','RIGHT')
						->where('objectName=?',$targetObject)
						->fetch();
					//已存在文件，编号
					if($rs_Exist){
						$fileLastDotPos = strripos($targetObject, '.');
						$rs_ExistSeq = Db::select('securityName','objectName','objectId')
								->from('security','object')
								->join('object','security.securityId = object.securityId','RIGHT')
								->where('objectName like ?',substr($targetObject, 0, $fileLastDotPos).'(%).%')
								->where('objectName not like?',substr($targetObject, 0, $fileLastDotPos).'(%)(%).%')
								->fetchAll();
						if(!$rs_ExistSeq){
							$targetObject = substr($targetObject, 0, $fileLastDotPos)."(1)".substr($targetObject, $fileLastDotPos);
						}else{
							$seqArray = array();
							foreach($rs_ExistSeq as $row_ExistSeq):
								$leftHalfPos = strrpos($row_ExistSeq['objectName'],"(");
								$rightHalfPos = strrpos($row_ExistSeq['objectName'],")");
								$seq = substr($row_ExistSeq['objectName'], $leftHalfPos+1, $rightHalfPos-$leftHalfPos-1);
								array_push($seqArray, $seq);
							endforeach;
							//冒泡排序
							$cnt = count($seqArray);
							for($i = 0; $i < $cnt; $i++){
								for($j = 0; $j < $cnt - $i - 1; $j++){
									if($seqArray[$j] > $seqArray[$j+1]){
										$temp = $seqArray[$j];
										$seqArray[$j] = $seqArray[$j+1];
										$seqArray[$j+1] = $temp;
									}
								}
							}
							for($i = 1, $j = 0; $j < $cnt; $j++,$i++){
								if($i != $seqArray[$j])
									break;
							}
							$targetObject = substr($targetObject, 0, $fileLastDotPos)."(".$i.")".substr($targetObject, $fileLastDotPos);
						}
					}
					//选择复制的文件与当前文件的安全策略一致
					if($rs_copy['securityName'] == $securityName){
						switch ($rs_copy['securityName']) {
							//文件存在百度云
							case 'baiduCloud':
								$GLOBALS['baiduClient']->copyObject($baiduBucketName, $rs_copy['objectName'], $baiduBucketName, $targetObject);
								break;
							//存在阿里云
							case 'aliCloud':
								try{
									$options = array();
							        $aliOssClient->copyObject($aliBucketName, $rs_copy['objectName'], $aliBucketName, $targetObject, $options);
							    } catch(OssException $e) {
							        pexceptionHandle($e);
							        return;
							    }
								break;
							//分别存在百度云、阿里云 && 一半存在百度云，一半存在阿里云
							case 'dualStorage':
							case 'halfStorage':
								$GLOBALS['baiduClient']->copyObject($baiduBucketName, $rs_copy['objectName'], $baiduBucketName, $targetObject);
								try{
									$options = array();
							        $aliOssClient->copyObject($aliBucketName, $rs_copy['objectName'], $aliBucketName, $targetObject, $options);
							    } catch(OssException $e) {
							        pexceptionHandle($e);
							        return;
							    }
								break;
							default:
								break;
						}
						$rs_copyfile = Db::insert('object')->rows(array(
								'userId' => $_SESSION['userId'],
								'securityId' => $rs_copy['securityId'],
								'objectName' => $targetObject,
								'dataSecretEncrypted' => $rs_copy['dataSecretEncrypted'],
								'objectSize' => $rs_copy['objectSize'],
								'objectDate' => $time,
							))->query();	
					}else{
						switch ($rs_copy['securityName']) {
							//文件存在百度云
							case 'baiduCloud':
								//下载object到本地变量
								$content = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $rs_copy['objectName']);
								break;
							
							//文件存在阿里云 && 文件分别存在百度云和阿里云
							case 'aliCloud':
							case 'dualStorage':
								// 下载object到本地变量
								$content = $aliOssClient->getObject($aliBucketName, $rs_copy['objectName']);
								break;

							//百度云、阿里云各存一半
							case 'halfStorage':
								// 下载object到本地变量
								$content_1 = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $rs_copy['objectName']);
								$content_2 = $aliOssClient->getObject($aliBucketName, $rs_copy['objectName']);
								$content = $content_1.$content_2;
								break;

							default:
								break;
						}
						switch($securityName){
							//存在百度云
							case 'baiduCloud':
								$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $targetObject, $content);
								$rs_baiduobject = Db::insert('object')->rows(array(
									'userId' => $_SESSION['userId'],
									'securityId' => $rs_security['securityId'],
									'objectName' => $targetObject,
									'dataSecretEncrypted' => $rs_copy['dataSecretEncrypted'],
									'objectSize' => $rs_copy['objectSize'],
									'objectDate' => $time,
								))->query();
								break;

							//存在阿里云
							case 'aliCloud':
								// 简单上传变量的内容到文件
								try{
									$aliOssClient->putObject($aliBucketName, $targetObject, $content);
								} catch (OssException $e) {
							        exceptionHandle($e);
							        return;
								}	
								//插入数据库
								$rs_aliobject = Db::insert('object') ->rows(array(
									'userId' => $_SESSION['userId'],
									'securityId' => $rs_security['securityId'],
									'objectName' => $targetObject,
									'dataSecretEncrypted' => $rs_copy['dataSecretEncrypted'],
									'objectSize' => $rs_copy['objectSize'],
									'objectDate' => $time,
								))->query();
								break;

							//分别存在百度云、阿里云
							case 'dualStorage':
								$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $targetObject, $content);
								try{
									$aliOssClient->putObject($aliBucketName, $targetObject, $content);
								} catch (OssException $e) {
							        exceptionHandle($e);
							        return;
								}	
								//插入数据库
								$rs_dualobject = Db::insert('object')->rows(array(
									'userId' => $_SESSION['userId'],
									'securityId' => $rs_security['securityId'],
									'objectName' => $targetObject,
									'dataSecretEncrypted' => $rs_copy['dataSecretEncrypted'],
									'objectSize' => $rs_copy['objectSize'],
									'objectDate' => $time,
								))->query();					
								break;

							//百度云、阿里云各存一半
							case 'halfStorage':
								$data_baidu = substr($content, 0, strlen($content)/2);
								$data_ali = substr($content, strlen($content)/2);
								$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $targetObject, $data_baidu);
								try{
									$aliOssClient->putObject($aliBucketName, $targetObject, $data_ali);
								} catch (OssException $e) {
							        exceptionHandle($e);
							        return;
								}	
								//插入数据库
								$rs_halfobject = Db::insert('object')->rows(array(
									'userId' => $_SESSION['userId'],
									'securityId' => $rs_security['securityId'],
									'objectName' => $targetObject,
									'dataSecretEncrypted' => $rs_copy['dataSecretEncrypted'],
									'objectSize' => $rs_copy['objectSize'],
									'objectDate' => $time,
								))->query();						
								break;

							default:
								break;
						}
					}
				}    		
				
				/*********************再删除原文件**********************/
				$rs_del = Db::select('securityName','objectName')
						->from('security','object')
						->join('object','security.securityId = object.securityId','RIGHT')
						->where('objectId=?',$objectId)
						->limit(1)
						->fetch();
				$delObjectList = explode('/', $rs_del['objectName']);
				$delprefix = $delObjectList[count($delObjectList)-1];
				//删除文件夹
				if($delprefix == ""){
					$rs_fileDel = Db::select('securityName', 'objectName', 'objectId')
								->from('security','object')
								->join('object', 'security.securityId = object.securityId', 'RIGHT')
								->where('objectName like ?', '%'.$delObjectList[count($delObjectList)-2].'/%')
								->where('objectName like ?', $rs_del['objectName'].'%')
								->fetchAll();
					//删除对应文件夹树
					//第一级文件夹
					if(count($delObjectList) == 2){
						$rs_selfId = Db::select('folderId')
									->from('foldertree')
									->where('folderName=?',$delObjectList[count($delObjectList)-2])
									->where('parentId=?', 1)
									->fetch();
						$selfId = $rs_selfId['folderId'];
					}else{
						$rs_selfId = Db::select('folderId')
								->from('foldertree')
								->where('folderName=?',$delObjectList[count($delObjectList)-2])
								->fetchAll();
						foreach($rs_selfId as $row_selfId):
							$path = Controller_Treenode::get_path($row_selfId['folderId']);
							//选出所选择的文件夹
							//path 多出 0和全部文件   delObjectList 多出本身和空格
							if(count($path) == count($delObjectList)){
								$foldercount = 0;
								for($i=2, $j=0; $i<count($path); $i++,$j++){
									if($path[$i]['folderName'] == $delObjectList[$j]){
										$foldercount++;
									}
								}
								if($foldercount == count($path)-2){
									$selfId = $row_selfId['folderId'];
									break;
								}
							}
						endforeach;
					}
					Controller_Treenode::delete_children($selfId);
					$rs_folderDel = Db::delete('foldertree')->where('parentId=?',$selfId)->query();
					$rs_folderSelfDel = Db::delete('foldertree')->where('folderId=?',$selfId)->query();
					foreach($rs_fileDel as $row_fileDel):
						switch ($row_fileDel['securityName']) {
							//文件存在百度云
							case 'baiduCloud':
								$GLOBALS['baiduClient']->deleteObject($baiduBucketName, $row_fileDel['objectName']);
								break;
							//存在阿里云
							case 'aliCloud':
								try{
									$aliOssClient->deleteObject($aliBucketName, $row_fileDel['objectName']);
								} catch (OssException $e) {
							        exceptionHandle($e);
							        return;
								}
								break;
							//分别存在百度云、阿里云 && 一半存在百度云，一半存在阿里云
							case 'dualStorage':
							case 'halfStorage':
								$GLOBALS['baiduClient']->deleteObject($baiduBucketName, $row_fileDel['objectName']);
								try{
									$aliOssClient->deleteObject($aliBucketName, $row_fileDel['objectName']);
								} catch (OssException $e) {
							        exceptionHandle($e);
							        return;
								}
								break;
							default:
								break;
						}
						$rs_filedelsql = Db::delete('object')->where('objectId=?',$row_fileDel['objectId'])->query();
					endforeach;
				}else{
					switch ($rs_del['securityName']) {
						//文件存在百度云
						case 'baiduCloud':
							$GLOBALS['baiduClient']->deleteObject($baiduBucketName, $rs_del['objectName']);
							break;
						//存在阿里云
						case 'aliCloud':
							try{
								$aliOssClient->deleteObject($aliBucketName, $rs_del['objectName']);
							} catch (OssException $e) {
						        exceptionHandle($e);
						        return;
							}
							break;
						//分别存在百度云、阿里云 && 一半存在百度云，一半存在阿里云
						case 'dualStorage':
						case 'halfStorage':
							$GLOBALS['baiduClient']->deleteObject($baiduBucketName, $rs_del['objectName']);
							try{
								$aliOssClient->deleteObject($aliBucketName, $rs_del['objectName']);
							} catch (OssException $e) {
						        exceptionHandle($e);
						        return;
							}
							break;
						default:
							break;
					}
					$rs_delsql = Db::delete('object')->where('objectId=?',$objectId)->query();	
				}	
				echo "<script>window.location='".SITE_URL."'</script>";			
			}
		}
	
		include 'view/index.php';
	}

	static public function login(){
		if(!empty($_POST)){
			$_POST['userName'] = isset($_POST['userName']) ? trim($_POST['userName']) : '';
			$_POST['pass'] = isset($_POST['pass']) ? trim($_POST['pass']) : '';
			$verify=$_SESSION['verify'];
			$chaptcha=$_POST['chaptcha'];

			if($verify != $chaptcha){
				$errmsg = '验证码错误';
			}else{
				$rs = Db::select('userName', 'userId')
						->from('user')
						->where('userName=?', $_POST['userName'])
						->where('passwordHashs=?', md5(md5($_POST['pass'])))
						->limit(1)
						->fetch();
				if($rs){
					$_SESSION['userId'] = $rs['userId'];
					$_SESSION['userName'] = $rs['userName'];
					$_SESSION['passwordHash'] = md5($_POST['pass']);
					redirect( SITE_URL );
				} else {
					$errmsg = '用户名或密码错误';
				}
			}
		}
		include 'view/login.php';
	}

	static public function logout(){
		session_destroy();
		redirect( SITE_URL.'login' );
	}

	static public function baseset(){
		if(!empty($_SESSION)){
			$rs = Db::select('userName','passwordHashs','userSecretHash','userSecretEncrypted')
					->from('user')
					->where('userId=?', $_SESSION['userId'])
					->limit(1)
					->fetch();

			//获得用户密钥
			$userSecretDecrypted = Controller_Cryption::decryption($_SESSION["passwordHash"], $rs["userSecretEncrypted"]);

			if(!empty($_POST)){
				if(!isset($_POST["oldPassword"]) || empty($_POST["oldPassword"])
					|| !isset($_POST["newPassword1"]) || empty($_POST["newPassword1"])){
					echo "<script>alert('原始密码或新设密码不能为空');</script>";
					echo "<script>window.location='".SITE_URL."baseSet</script>";
				}else{
					$oldPassword = md5(md5($_POST['oldPassword']));
					if($oldPassword != $rs['passwordHashs']){
						echo "<script>alert('原始密码错误');</script>";
						echo "<script>window.location='".SITE_URL."baseSet</script>";
					}else{
						$passwordHashs = md5(md5($_POST['newPassword1']));
						$_SESSION['passwordHash'] = md5($_POST['newPassword1']);

						//重新加密用户密钥
						$ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
						$iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
						$userSecretEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $_SESSION['passwordHash'], $userSecretDecrypted, MCRYPT_MODE_CBC, $iv);
						$userSecretEncrypted = trim(base64_encode($iv . $userSecretEncrypted));
						$userSecretHash = md5($userSecretEncrypted);
						$rs_update = Db::update('user')->rows(array(
									'passwordHashs' => $passwordHashs,
									'userSecretHash' => $userSecretHash,
									'userSecretEncrypted' => $userSecretEncrypted
									))->where('userId=?', $_SESSION['userId'])->query();
										
						if($rs_update){
							echo "<script>alert('修改成功！');</script>";
							echo "<script>window.location='".SITE_URL."baseSet</script>";
						}else{
							echo "<script>alert('修改失败！');</script>";
							echo "<script>window.location='".SITE_URL."baseSet</script>";
						}
					}
				}
			}
		}
		include 'view/baseSet.php';
	}

	static public function safeset(){
		if(!empty($_SESSION)){
			//获取默认的安全策略、存储空间的名称
			$rs_security = Db::select('securityName','securityId','bucketName')
					->from('security','user')
					->join('user','security.securityId = user.selectedSecurity','RIGHT')
					->where('userId=?', $_SESSION['userId'])
					->limit(1)
					->fetch();
			$securityName = $rs_security["securityName"];
			$bucketName = $rs_security["bucketName"];

			//获取阿里云bucket
			$aliOssClient = Controller_ALiYun_Common::getOssClient();
			if (is_null($aliOssClient)) exit(1);
			try{
				$aliBucketListInfo = $aliOssClient->listBuckets();
			}catch(OssException $e){
				exceptionHandle($e);
				return;
			}
			$aliBucketList = $aliBucketListInfo->getBucketList();
			foreach($aliBucketList as $aliBucket){
				if($aliBucket->getName() == $bucketName){
					$aliBucketName = $bucketName;
					break;
				}
			}

			//获取百度云bucket
			$exist = $GLOBALS['baiduClient']->doesBucketExist($bucketName);
			if($exist){
				$baiduBucketName = $bucketName;
			}

			//获得用户密钥
			$rs_usersecret = Db::select('userName','passwordHashs','userSecretHash','userSecretEncrypted')
					->from('user')
					->where('userId=?', $_SESSION['userId'])
					->limit(1)
					->fetch();
			$userSecretDecrypted = Controller_Cryption::decryption($_SESSION["passwordHash"], $rs_usersecret["userSecretEncrypted"]);

			//获取文件夹里面的内容
			if(!empty($_GET['folderName'])){
				//列出所有文件夹
				$sql_file = Db::select('objectName','objectSize','objectDate','objectId')
						->from('object')
						->where('userId=?', $_SESSION['userId'])
						->where('objectName like ?', $_GET['folderName'].'%/')
						->where('objectName NOT like ?', $_GET['folderName'].'%/%/');
				
				$sql_file->order('objectDate','DESC');
				$rs_file  = Db::fetchAll(Db::query($sql_file));

				//列出所有文件
				$sql_object = Db::select('objectName','objectSize','objectDate','objectId','securityName')
						->from('security','object')
						->join('object','security.securityId = object.securityId','RIGHT')
						->where('userId=?', $_SESSION['userId'])
						->where('objectName like ?', $_GET['folderName'].'%')
						->where('objectName NOT LIKE ?', $_GET['folderName'].'%/')
						->where('objectName NOT like ?', $_GET['folderName'].'%/%')
						->where('objectName !=?',$_GET['folderName']);

				$sql_object->order('objectDate','DESC');
				$rs_object  = Db::fetchAll(Db::query($sql_object));
			}else{
				//列出所有文件夹
				$sql_file = Db::select('objectName','objectSize','objectDate','objectId')
						->from('object')
						->where('userId=?', $_SESSION['userId'])
						->where('objectName like ?', '%/')
						->where('objectName NOT like ?', '%/%/');
				
				$sql_file->order('objectDate','DESC');
				$rs_file  = Db::fetchAll(Db::query($sql_file));

				//列出所有文件
				$sql_object = Db::select('objectName','objectSize','objectDate','objectId','securityName')
						->from('security','object')
						->join('object','security.securityId = object.securityId','RIGHT')
						->where('userId=?', $_SESSION['userId'])
						->where('objectName NOT LIKE ?', '%/')
						->where('objectName NOT LIKE ?', '%/%');

				$sql_object->order('objectDate','DESC');
				$rs_object  = Db::fetchAll(Db::query($sql_object));				
			}

			//搜索
			if(!empty($_GET['kw'])){
				//列出所有符合条件的文件夹
				$sql_file = Db::select('objectName','objectSize','objectDate','objectId')
						->from('object')
						->where('userId=?', $_SESSION['userId'])
						->where('objectName like ?', '%'.$_GET['kw'].'%/');
				
				$sql_file->order('objectDate','DESC');
				$rs_file  = Db::fetchAll(Db::query($sql_file));

				//列出所有文件
				$sql_object = Db::select('objectName','objectSize','objectDate','objectId','securityName')
						->from('security','object')
						->join('object','security.securityId = object.securityId','RIGHT')
						->where('userId=?', $_SESSION['userId'])
						->where('objectName like ?', '%'.$_GET['kw'].'%')
						->where('objectName NOT LIKE ?', '%'.$_GET['kw'].'%/');
			
				$sql_object->order('objectDate','DESC');
				$rs_object  = Db::fetchAll(Db::query($sql_object));
			}

			//设置默认安全策略
			if(!empty($_POST['safeSet'])){
				$rs_security = Db::select('securityId')
					->from('security')
					->where('securityName=?', $_POST['safeSet'])
					->limit(1)
					->fetch();
				$rs_update = Db::update('user') ->rows(array(
							'selectedSecurity' => $rs_security['securityId'],
				))->where('userId=?',$_SESSION['userId'])->limit(1)->query();
				if($rs_update){
					echo "<script>alert('修改安全策略成功');</script>";
					echo "<script>window.location='".SITE_URL."safeset/';</script>";
				}else{
					echo "<script>alert('修改安全策略失败');</script>";
					echo "<script>window.location='".SITE_URL."safeset';</script>";
				}
			}

			//修改安全策略
			if(!empty($_GET['securityId']) && !empty($_GET['securityName'])){
				//安全策略列表
				$securityList = explode(',', $_GET['securityId']);
				$selectedSecurityId = Db::select('securityId')
									->from('security')
									->where('securityName=?',$_GET['securityName'])
									->limit(1)
									->fetch();
				for($i = 0; $i < count($securityList)-1; $i++){
					$time = time();
					$rs_safe = Db::select('securityName','objectName','object.securityId','dataSecretEncrypted','objectSize','objectDate','objectId')
							->from('security','object')
							->join('object','security.securityId = object.securityId','RIGHT')
							->where('objectId=?',$securityList[$i])
							->limit(1)
							->fetch();
					
					$safeFileList = explode('/', $rs_safe['objectName']);
					$safeFileName = $safeFileList[count($safeFileList)-1];
					//修改文件夹安全策略
					if($safeFileName == ""){
						$rs_fileSafe = Db::select('securityName', 'objectName', 'objectId','object.securityId','dataSecretEncrypted','objectSize')
									->from('security','object')
									->join('object', 'security.securityId = object.securityId', 'RIGHT')
									->where('objectName like ?', '%'.$safeFileList[count($safeFileList)-2].'/%')
									->where('objectName like ?', $rs_safe['objectName'].'%')
									->fetchAll();
						
						foreach($rs_fileSafe as $row_fileSafe):
							$fileList = explode('/', $row_fileSafe['objectName']);
							//文件夹里面的文件的安全策略与选择的不一样
							if($fileList[count($fileList)-1] != "" && $row_fileSafe['securityName'] != $_GET['securityName']){
									//先下载到本地
									switch ($row_fileSafe['securityName']) {
										//文件存在百度云
										case 'baiduCloud':
											//下载object到本地变量
											$content = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $row_fileSafe['objectName']);
											//删除云端上的数据
											$GLOBALS['baiduClient']->deleteObject($baiduBucketName, $row_fileSafe['objectName']);
											break;
										
										//文件存在阿里云
										case 'aliCloud':
											// 下载object到本地变量
											$content = $aliOssClient->getObject($aliBucketName, $row_fileSafe['objectName']);
											try{
												$aliOssClient->deleteObject($aliBucketName, $row_fileSafe['objectName']);
											} catch (OssException $e) {
										        exceptionHandle($e);
										        return;
											}
											break;
										//文件分别存在百度云和阿里云	
										case 'dualStorage':
											$content = $aliOssClient->getObject($aliBucketName, $row_fileSafe['objectName']);
											$GLOBALS['baiduClient']->deleteObject($baiduBucketName, $row_fileSafe['objectName']);
											try{
												$aliOssClient->deleteObject($aliBucketName, $row_fileSafe['objectName']);
											} catch (OssException $e) {
										        exceptionHandle($e);
										        return;
											}
											break;

										//百度云、阿里云各存一半
										case 'halfStorage':
											// 下载object到本地变量
											$content_1 = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $row_fileSafe['objectName']);
											$content_2 = $aliOssClient->getObject($aliBucketName, $row_fileSafe['objectName']);
											$content = $content_1.$content_2;
											$GLOBALS['baiduClient']->deleteObject($baiduBucketName, $row_fileSafe['objectName']);
											try{
												$aliOssClient->deleteObject($aliBucketName, $row_fileSafe['objectName']);
											} catch (OssException $e) {
										        exceptionHandle($e);
										        return;
											}
											break;

										default:
											break;
									}
									switch($_GET['securityName']){
										//存在百度云
										case 'baiduCloud':
											$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $row_fileSafe['objectName'], $content);
											$rs_baiduobject = Db::update('object') ->rows(array(
															'securityId' => $selectedSecurityId['securityId'],
															'objectDate' => $time,
											))->where('objectId=?',$row_fileSafe['objectId'])->limit(1)->query();
											break;

										//存在阿里云
										case 'aliCloud':
											// 简单上传变量的内容到文件
											try{
												$aliOssClient->putObject($aliBucketName, $row_fileSafe['objectName'], $content);
											} catch (OssException $e) {
										        exceptionHandle($e);
										        return;
											}	
											$rs_alibject = Db::update('object') ->rows(array(
															'securityId' => $selectedSecurityId['securityId'],
															'objectDate' => $time,
											))->where('objectId=?',$row_fileSafe['objectId'])->limit(1)->query();
											break;

										//分别存在百度云、阿里云
										case 'dualStorage':
											$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $row_fileSafe['objectName'], $content);
											try{
												$aliOssClient->putObject($aliBucketName, $row_fileSafe['objectName'], $content);
											} catch (OssException $e) {
										        exceptionHandle($e);
										        return;
											}	
											$rs_dualobject = Db::update('object') ->rows(array(
															'securityId' => $selectedSecurityId['securityId'],
															'objectDate' => $time,
											))->where('objectId=?',$row_fileSafe['objectId'])->limit(1)->query();	
											break;

										//百度云、阿里云各存一半
										case 'halfStorage':
											$data_baidu = substr($content, 0, strlen($content)/2);
											$data_ali = substr($content, strlen($content)/2);
											$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $row_fileSafe['objectName'], $data_baidu);
											try{
												$aliOssClient->putObject($aliBucketName, $row_fileSafe['objectName'], $data_ali);
											} catch (OssException $e) {
										        exceptionHandle($e);
										        return;
											}	
											$rs_halfobject = Db::update('object') ->rows(array(
															'securityId' => $selectedSecurityId['securityId'],
															'objectDate' => $time,
											))->where('objectId=?',$row_fileSafe['objectId'])->limit(1)->query();	
											break;

										default:
											break;
									}
							}
						endforeach;
					}else{
						if($rs_safe['securityName'] != $_GET['securityName']){
							//先下载到本地
							switch ($rs_safe['securityName']) {
								//文件存在百度云
								case 'baiduCloud':
									//下载object到本地变量
									$content = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $rs_safe['objectName']);
									//删除云端上的数据
									$GLOBALS['baiduClient']->deleteObject($baiduBucketName, $rs_safe['objectName']);
									break;
								
								//文件存在阿里云 && 文件分别存在百度云和阿里云
								case 'aliCloud':
									// 下载object到本地变量
									$content = $aliOssClient->getObject($aliBucketName, $rs_safe['objectName']);
									try{
										$aliOssClient->deleteObject($aliBucketName, $rs_safe['objectName']);
									} catch (OssException $e) {
								        exceptionHandle($e);
								        return;
									}
									break;
								case 'dualStorage':
									$content = $aliOssClient->getObject($aliBucketName, $rs_safe['objectName']);
									$GLOBALS['baiduClient']->deleteObject($baiduBucketName, $rs_safe['objectName']);
									try{
										$aliOssClient->deleteObject($aliBucketName, $rs_safe['objectName']);
									} catch (OssException $e) {
								        exceptionHandle($e);
								        return;
									}
									break;

								//百度云、阿里云各存一半
								case 'halfStorage':
									// 下载object到本地变量
									$content_1 = $GLOBALS['baiduClient']->getObjectAsString($baiduBucketName, $rs_safe['objectName']);
									$content_2 = $aliOssClient->getObject($aliBucketName, $rs_safe['objectName']);
									$content = $content_1.$content_2;
									$GLOBALS['baiduClient']->deleteObject($baiduBucketName, $rs_safe['objectName']);
									try{
										$aliOssClient->deleteObject($aliBucketName, $rs_safe['objectName']);
									} catch (OssException $e) {
								        exceptionHandle($e);
								        return;
									}
									break;

								default:
									break;
							}
							switch($_GET['securityName']){
								//存在百度云
								case 'baiduCloud':
									$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $rs_safe['objectName'], $content);
									$rs_baiduobject = Db::update('object') ->rows(array(
													'securityId' => $selectedSecurityId['securityId'],
													'objectDate' => $time,
									))->where('objectId=?',$rs_safe['objectId'])->limit(1)->query();
									break;

								//存在阿里云
								case 'aliCloud':
									// 简单上传变量的内容到文件
									try{
										$aliOssClient->putObject($aliBucketName, $rs_safe['objectName'], $content);
									} catch (OssException $e) {
								        exceptionHandle($e);
								        return;
									}	
									$rs_alibject = Db::update('object') ->rows(array(
													'securityId' => $selectedSecurityId['securityId'],
													'objectDate' => $time,
									))->where('objectId=?',$rs_safe['objectId'])->limit(1)->query();
									break;

								//分别存在百度云、阿里云
								case 'dualStorage':
									$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $rs_safe['objectName'], $content);
									try{
										$aliOssClient->putObject($aliBucketName, $rs_safe['objectName'], $content);
									} catch (OssException $e) {
								        exceptionHandle($e);
								        return;
									}	
									$rs_dualobject = Db::update('object') ->rows(array(
													'securityId' => $selectedSecurityId['securityId'],
													'objectDate' => $time,
									))->where('objectId=?',$rs_safe['objectId'])->limit(1)->query();	
									break;

								//百度云、阿里云各存一半
								case 'halfStorage':
									$data_baidu = substr($content, 0, strlen($content)/2);
									$data_ali = substr($content, strlen($content)/2);
									$GLOBALS['baiduClient']->putObjectFromString($baiduBucketName, $rs_safe['objectName'], $data_baidu);
									try{
										$aliOssClient->putObject($aliBucketName, $rs_safe['objectName'], $data_ali);
									} catch (OssException $e) {
								        exceptionHandle($e);
								        return;
									}	
									$rs_halfobject = Db::update('object') ->rows(array(
													'securityId' => $selectedSecurityId['securityId'],
													'objectDate' => $time,
									))->where('objectId=?',$rs_safe['objectId'])->limit(1)->query();	
									break;

								default:
									break;
							}
						}
					}
				}
				echo "<script>window.location='".SITE_URL."safeset';</script>";
			}
		}
		include 'view/safeSet.php';
	}
	static public function checkfile(){
		if(!empty($_GET['objectName'])){
			$rs_Exist = Db::select('securityName','objectName','objectId')
						->from('security','object')
						->join('object','security.securityId = object.securityId','RIGHT')
						->where('objectName=?',$_GET['objectName'])
						->fetch();
			if($rs_Exist){
				$result = '{"success":false,"msg":"该文件已存在,是否替换？"}';
			}else{
				$result = '{"success":true,"msg":""}';
			}
			echo $result;
		}
	}

}