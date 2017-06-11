<?php
	require_once __DIR__ . '/fileutil.php';
	class Controller_Cryption{
		/**
		 *对明文文件信息进行加密
		 */
		static public function fileencryption($oldFileName,$key){
			$ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
			$iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
			$data = self::openFile($oldFileName);
			$encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv);
			$encrypted = trim($iv.$encrypted);
			return $encrypted;
		} 
		/**
		 *对密文文件进行解密
		 */
		static public function filedecryption($data, $key, $folderName){
			$iv_dec = substr($data, 0, 16);
			$decrypted = substr($data, 16);
			$decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $decrypted, MCRYPT_MODE_CBC, $iv_dec),"\0");
			self::saveToFile($folderName, $decrypted);
		}
		/**
		 *将文件打开
		 */
		static public function openFile($fileName){
			$fileName = iconv("utf-8","gb2312",$fileName);
			try{
				$File = fopen($fileName, "rb");
				if(!$File){
					throw new Exception("原文件打开失败");
				}
				return fread($File, filesize($fileName));
			}catch(Exception $e){
				exceptionHandle($e);
			}
		}

		/**
		 *将密文保存到文件中
		 */
		static public function saveToFile($fileName,$content){
			//中文文件名正确显示
			$fileName = iconv("utf-8", "gb2312", $fileName);
			try{
				Controller_fileutil::createFile($fileName, true);
				$file = fopen($fileName,"wb");
				if(!$file){
					throw new Exception("加密文件".$fileName."打开失败");
				}
				fwrite($file,$content);
				fclose($file);
			}catch(Exception $e){
				exceptionHandle($e);
			}
		}

		//处理特殊字符改进版：base64_encode && base64_decode
		static public function urlsafe_b64encode($string){
		  $data = base64_encode($string);
		  $data = str_replace(array('+','/','='),array('-','_','.'),$data);
		  return $data;
		}

		static public function urlsafe_b64decode($string){
		  $data = str_replace(array('-','_','.'),array('+','/','='),$string);
		  $mod4 = strlen($data) % 4;
		  if ($mod4) {
		    $data .= substr('====', $mod4);
		  }
		  return base64_decode($data);
		}
		/**
		 *对明文信息（非文件）进行加密
		 */
		static public function encryption($key, $data){
			$ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
			$iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
			$encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv);
			$encrypted = trim(self::urlsafe_b64encode($iv.$encrypted));
			return $encrypted;
		} 
		/**
		 *对密文信息（非文件）进行解密
		 */
		static public function decryption($key, $data){
			$decrypted = self::urlsafe_b64decode($data);
			$iv_dec = substr($decrypted, 0, 16);
			$decrypted = substr($decrypted, 16);
			$decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $decrypted, MCRYPT_MODE_CBC, $iv_dec),"\0");
			return $decrypted;
		}
		/**
		 * PHP ZipArchive压缩文件夹，实现将目录及子目录中的所有文件压缩为zip文件
		 * @param string $folderPath 要压缩的目录路径
		 * @param string $zipAs 压缩文件的文件名，可以带路径
		 * @return bool 成功时返回true，否则返回false
		 */
		static public function zipFolder($folderPath, $zipAs){
			$folderPath = (string)$folderPath;
			$zipAs = (string)$zipAs;
			if(!class_exists('ZipArchive')){
				return false;
			}
			if(!$files=Controller_Cryption::scanFolder($folderPath, true, true)){
				return false;
			}
			$za = new ZipArchive;
			if(true!==$za->open($zipAs, ZipArchive::OVERWRITE | ZipArchive::CREATE)){
				return false;
			}
			foreach($files as $aPath => $rPath){
				$za->addFile($aPath, $rPath);
			}
			if(!$za->close()){
				return false;
			}
			return true;
		}

		/**
		 * 扫描文件夹，获取文件列表
		 * @param string $path 需要扫描的目录路径
		 * @param bool   $recursive 是否扫描子目录
		 * @param bool   $noFolder 结果中只包含文件，不包含任何目录，为false时，文件列表中的目录统一在末尾添加/符号
		 * @param bool   $returnAbsolutePath 文件列表使用绝对路径，默认将返回相对于指定目录的相对路径
		 * @param int    $depth 子目录层级，此参数供系统使用，禁止手动指定或修改
		 * @return array|bool 返回目录的文件列表，如果$returnAbsolutePath为true，返回索引数组，否则返回键名为绝对路径，键值为相对路径的关联数组
		 */
		static public function scanFolder($path='', $recursive=true, $noFolder=true, $returnAbsolutePath=false,$depth=0){
			$path = (string)$path;
			if(!($path=realpath($path))){
				return false;
			}
			$path = str_replace('\\','/',$path);
			if(!($h=opendir($path))){
				return false;
			}
			$files = array();
			static $topPath;
			$topPath = $depth===0||empty($topPath)?$path:$topPath;
			while(false!==($file=readdir($h))){
				if($file!=='..' && $file!=='.'){
					$fp = $path.'/'.$file;
					if(!is_readable($fp)){
						continue;
					}
					if(is_dir($fp)){
						$fp .= '/';
						if(!$noFolder){
								$files[$fp] = $returnAbsolutePath?$fp:ltrim(str_replace($topPath,'',$fp),'/');
						}
						if(!$recursive){
								continue;
						}
						//$function = __FUNCTION__;
						$subFolderFiles = Controller_Cryption::scanFolder($fp, $recursive, $noFolder, $returnAbsolutePath, $depth+1);
						if(is_array($subFolderFiles)){
								$files = array_merge($files, $subFolderFiles);
						}
					}else{
							$files[$fp] = $returnAbsolutePath?$fp:ltrim(str_replace($topPath,'',$fp),'/');
					}
				}
			}
			return $returnAbsolutePath?array_values($files):$files;
		}
		/**
		 *将服务器中的文件下载到本地
		 */
		static public function downloadFile($filePath, $fileName){
			header("Content-type:text/html;charset=utf-8");
			$fileName = iconv("utf-8", "gb2312", $fileName);
			$filePath = $filePath.$fileName;
			try{
				$File = fopen($filePath, "rb");
				if(!$File){
					throw new Exception("下载——解密文件打开失败");
				}
			}catch(Exception $e){
				exceptionHandle($e);
			}
			$fileSize = filesize($filePath);

			Header("Content-type:application/octet-stream");
			Header("Accept-Ranges:bytes");
			Header("Accept-Length:".$fileSize);
			Header("Content-Disposition:attachment;filename=".$fileName);
			$buffer = 1024;
			$file_count = 0;

			while(!feof($File) && $file_count < $fileSize){
				$file_con = fread($File, $buffer);
				$file_count += $buffer;
				echo $file_con;
			}
			fclose($File);
			//删除服务器上的原文件
			if(!unlink($filePath)){
				echo "<script>alert('Error deleting ".$filePath." on Server');</script>";
			}
		}
	}
?>
