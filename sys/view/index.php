<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="Eve">

    <title>多云存储主页</title>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>res/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>res/font-awesome-4.5.0/css/font-awesome.css">
    <!--[if IE 7]>
    <link rel="stylesheet" href="assets/css/font-awesome-ie7.min.css">
    <![endif]-->

    <!-- Custom styles for this template -->
    <link href="<?php echo SITE_URL; ?>res/css/index.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>res/css/common.css"  rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>res/css/Dialog.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>res/css/zTreeStyle/zTreeStyle.css" rel="stylesheet">
    <!-- Just for debugging purposes. Don't actually copy these 2 lines! -->
    <!--[if lt IE 9]><script src="../../assets/js/ie8-responsive-file-warning.js"></script><![endif]-->
    <script src="<?php echo SITE_URL; ?>res/assets/js/ie-emulation-modes-warning.js"></script>
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>

  <body>

    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container-fluid">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="fa fa-bars fa-lg white"></span>
          </button>
          <a class="navbar-brand" href="<?php echo SITE_URL; ?>">多云存储</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
          <ul class="nav navbar-nav navbar-right">
            <li><a href="<?php echo SITE_URL; ?>baseset"><i class="fa fa-user"></i><?php echo $_SESSION['userName']; ?></a></li>
            <li><a href="<?php echo SITE_URL; ?>logout"><i class="fa fa-sign-out"></i>退出</a></li>
          </ul>
          <form class="navbar-form navbar-right" method="GET" action="">
            <input type="text" class="form-control" name="kw" placeholder="Search...">
          </form>
        </div>
      </div>
    </nav>

    <div class="container-fluid">
      <div class="row">
        <!--侧边菜单栏-->
        <div class="col-sm-3 col-md-2 sidebar">
          <ul class="nav nav-sidebar">
            <li class="active"><a class="nav-a" href="<?php echo SITE_URL; ?>"><i class="fa fa-home"></i>全部文件<span class="sr-only">(current)</span></a></li>
            <li><a class="nav-a" href="<?php echo SITE_URL; ?>safeset"><i class="fa fa-lock"></i>安全策略</a></li>
            <li><a class="nav-a" href="<?php echo SITE_URL; ?>baseset"><i class="fa fa-cogs"></i>基本设置</a></li>
          </ul>
        </div>
        <!--侧边菜单栏结束-->

        <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
          <div class="well bgwhite">
            <form role="form" id="uploadform" method="POST" action="" enctype="multipart/form-data">
              <div class="fileUpload btn btn-primary">
                <i class="fa fa-cloud-upload"></i>上传文件</button>
                <input type="file" name='uploadfile' id="uploadfile" class="upload"/>
              </div>
              <button type="button" class="btn btn-primary" id="addnewfile"><i class="fa fa-plus-circle"></i>新建文件夹</button>
            </form>
          </div>

          <div class="file_detail">
            <div class="all_files">
              <a href="<?=SITE_URL;?>" style="margin-left:10px;">全部文件</a>
              <?php
                if(!empty($_GET['kw'])){
                  echo "&gt;搜索：".$_GET['kw'];
                }else if(!empty($_GET['folderName'])){
                  $folderList = explode('/', $_GET['folderName']);
                  $folder_name = "";
                  for($i = 0; $i < count($folderList)-1; $i++){
                    echo "&gt;";
                    $folder_name = $folder_name.$folderList[$i]."/";
                    echo "<a href='".SITE_URL."?folderName=".$folder_name."'>".$folderList[$i]."</a>";
                  }
                }
              ?>
            </div>
            <!--文件操作-->
            <div class="file_action">
              <div class="tools">
                <input type="checkbox" name="select_file"/>已选中<span class="length"><span class="count">0</span>个文件</span>
              </div>
              <div class="tools_all">
                <input type="checkbox" name="total_files" id="total_files"/>共<span class="total">0</span>个文件/文件夹
              </div>
              <div class="buttons">
                <button type="button" class="btn btn-info btn-sm mr5" id="download"><i class="fa fa-cloud-download"></i>下载</button>
                <button type="button" class="btn btn-info btn-sm mr5" id="delete"><i class="fa fa-trash"></i>删除</button>
                <button type="button" class="btn btn-info btn-sm mr5" id="remove"><i class="fa fa-cut"></i>移动到</button>
                <button type="button" class="btn btn-info btn-sm mr5" id="copy"><i class="fa fa-copy"></i>复制到</button>
                <button type="button" class="btn btn-info btn-sm" id="rename"><i class="fa fa-edit"></i>重命名</button>
              </div>
              <!--文件列表-->
              <div class="file_list mt5" id="file_list">
                <form role="form" id="newfileform" method="POST" action="">
                  <input type="hidden" id="checkedId" name="checkedId" value="">
                  <table>
                    <colgroup>
                      <col width="3%">
                      <col width="2%">
                      <col width="38%">
                      <col width="5%">
                      <col width="14%">
                      <col width="21%">
                      <col width="17%">  
                    </colgroup>
                    <tr>
                      <th></th>
                      <th colspan="2">文件名</th>
                      <th></th>
                      <th>大小</th>
                      <th>修改日期</th>
                      <?php if(!empty($_GET['kw'])){echo "<th>所在目录</th>";}?>
                    </tr>
                    <tr id="newfileitem">
                      <td><input type="checkbox" name=""></td>
                      <td><i class="fa fa-file yellow"></i></td>
                      <td><input type="text" name="newfile" class="newfile"><a class="btn_check newfile_check"><i class="fa fa-check"></i></a><a class="btn_close newfile_close"><i class="fa fa-close"></a></td>
                      <td></td>
                      <td>-</td>
                      <td>-</td>
                    </tr>
                  <?php foreach($rs_file as $row_file): 
                    if(!empty($_GET['folderName'])){
                      $length = strlen($_GET['folderName']);
                      $prefix = substr($row_file['objectName'], $length);
                    }else{
                      $prefix = $row_file['objectName'];
                    }
                    if(!empty($_GET['kw'])){
                      $fileList = explode('/', $row_file['objectName']);
                      $prefix = $fileList[count($fileList)-2];
                      //获得目录路径 $postFileName  所在目录$catalogue
                      $postFileName = "";
                      for($i = 0; $i < count($fileList)-2; $i++){
                        $postFileName = $postFileName.$fileList[$i]."/";
                      }
                      if(count($fileList) > 2){
                        $catalogue = "<a href=".SITE_URL."?folderName=".$postFileName.">".$fileList[count($fileList)-3]."</a>";
                      }else{
                        $catalogue = "<a href=".SITE_URL.">"."全部文件"."</a>";
                      }
                      if(strchr($prefix,$_GET['kw']) == false){
                        continue;
                      }
                    }
                  ?>
                    <tr>
                      <td><input type="checkbox" name="folderlist" id="<?=$row_file['objectId'];?>"></td>
                      <td><i class="fa fa-file yellow"></i></td>
                      <td class="fileshow"><a href="<?=SITE_URL;?>?folderName=<?=$row_file['objectName']?>"><?= $prefix; ?></a></td>
                      <td class="filehide"><input type="text" class="newfile" name="renamefile<?=$row_file['objectId'];?>" value="<?= $prefix; ?>"><a class="btn_check rename_check"><i class="fa fa-check"></i></a><a class="btn_close rename_close"><i class="fa fa-close"></i></a><span class="red ml5">重命名文件夹请以"/"结尾。</span></td>
                      <td><a href="<?=SITE_URL?>?Id=<?=$row_file['objectId'];?>"><i class="fa fa-download blue sicon"></i></a></td>
                      <!-- <td><i class="fa fa-chevron-circle-down blue sicon"></i></td> -->
                      <td>--</td>
                      <td><?= date('Y-m-d G:i:s',$row_file['objectDate']); ?></td>
                      <?php 
                        if(!empty($_GET['kw'])){
                          echo "<td>";
                          echo $catalogue;
                          echo "</td>";
                      }?>                    
                      </tr>
                  <?php endforeach; ?>
                  <?php foreach($rs_object as $row_object): 
                    if(!empty($_GET['folderName'])){
                      $objectlength = strlen($_GET['folderName']);
                      $objectprefix = substr($row_object['objectName'], $objectlength);
                    }else{
                      $objectprefix = $row_object['objectName'];
                    }
                    if(!empty($_GET['kw'])){
                      $objectList = explode('/', $row_object['objectName']);
                      $objectprefix = $objectList[count($objectList)-1];
                      //获得所在目录 $postObjectName  所在目录$catalogue
                      $postObjectName = "";
                      for($i = 0; $i < count($objectList)-1; $i++){
                        $postObjectName = $postObjectName.$objectList[$i]."/";
                      }
                      if(count($objectList) > 1){
                        $catalogue = "<a href=".SITE_URL."?folderName=".$postObjectName.">".$objectList[count($objectList)-2]."</a>";
                      }else{
                        $catalogue = "<a href=".SITE_URL.">"."全部文件"."</a>";
                      }
                      //除去后缀名
                      $postfix = "";
                      $postfixList = explode('.', $objectprefix);
                      for($i = 0; $i < count($postfixList)-1; $i++){
                        $postfix = $postfix.$postfixList[$i].'.';
                      }
                      if(strchr($postfix,$_GET['kw']) == false){
                        continue;
                      }
                    }
                  ?>
                    <tr>
                      <td><input type="checkbox" name="folderlist" id="<?=$row_object['objectId'];?>"></td>
                      <?php switch(getExt($row_object['objectName'])){
                        case 'doc':
                        case 'docx':
                          echo "<td><i class="."'fa fa-file-word-o blue'"."></i></td>";
                          break;
                        case 'xls':
                        case 'xlsx':
                          echo "<td><i class="."'fa fa-file-excel-o green'"."></i></td>";
                          break;
                        case 'ppt':
                        case 'pptx':
                          echo "<td><i class="."'fa fa-file-powerpoint-o deepyellow'"."></i></td>";
                          break;
                        case 'png':
                        case 'jpg':
                        case 'gif':
                        case 'psd':
                        case 'bmp':
                        case 'svg':
                          echo "<td><i class="."'fa fa-file-image-o red'"."></i></td>";
                          break;
                        case 'mp4':
                        case 'mpeg':
                        case 'mpg':
                        case 'dat':
                          echo "<td><i class="."'fa fa-file-video-o bluegray'"."></i></td>";
                          break;
                        case 'pdf':
                          echo "<td><i class="."'fa fa-file-pdf-o deepred'"."></i></td>";
                          break;
                        case 'cd':
                        case 'wave':
                        case 'aiff':
                        case 'au':
                        case 'mpeg':
                        case 'mp3':
                        case 'mpeg-4':
                        case 'wma':
                        case 'midi':
                        case 'amr':
                          echo "<td><i class="."'fa fa-file-sound-o purple'"."></i></td>";
                          break;
                        case '7z':
                        case 'rar':
                        case 'zip':
                        case 'zipx':
                          echo "<td><i class="."'fa fa-file-zip-o deepgreen'"."></i></td>";
                          break;
                        case 'txt':
                          echo "<td><i class="."'fa fa-file-text-o deepgray'"."></i></td>";
                          break;
                        case 'php':
                        case 'jsp':
                          echo "<td><i class="."'fa fa-file-code-o black'"."></i></td>";
                          break;
                        default:
                          echo "<td><i class="."'fa fa-file-o bluegreen'"."></i></td>";
                      }
                      ?>
                      <td class="fileshow"><?=$objectprefix?></td>
                      <td class="filehide"><input type="text" class="newfile" name="renamefile<?=$row_object['objectId'];?>" value="<?=$objectprefix?>"><a class="btn_check rename_check"><i class="fa fa-check"></i></a><a class="btn_close rename_close"><i class="fa fa-close"></a></td>
                      <td><a href="<?=SITE_URL?>?Id=<?=$row_object['objectId']?>"><i class="fa fa-download blue sicon"></i></a></td>
                      <!-- <td><i class="fa fa-chevron-circle-down blue sicon"></i></td> -->
                      <td><?php
                        if($row_object['objectSize'] > 1073741821){
                          echo number_format($row_object['objectSize']/1073741821,3)."GB";
                        }elseif($row_object['objectSize'] > 1048576){
                          echo number_format($row_object['objectSize']/1048576,3)."MB";
                        }else{
                          echo number_format($row_object['objectSize']/1024,3)."KB";
                        }
                      ?></td>
                      <td><?= date('Y-m-d G:i:s',$row_object['objectDate']); ?></td>
                      <?php if(!empty($_GET['kw'])){
                          echo "<td>";
                          echo $catalogue;
                          echo "</td>";
                       } ?>
                    </tr>
                  <?php endforeach; ?>
                  </table>
                </form>
              </div> 
            </div>
          </div>
          <div id="try"></div>
        </div>
      </div>
    </div>
    
    <!--hover菜单-->
    <!-- <div class="move_list hoverMenu">
      <ul>
        <li class="move_to">移动到</li>
        <li>复制到</li>
        <li>重命名</li>
        <li>删除</li>
      </ul>
    </div> -->
    <!--右键菜单-->
    <!-- <div class="move_list contextMenu" id="contextMenu">
      <ul>
        <li>打开</li>
        <li>下载</li>
        <li class="move_to">移动到</li>
        <li>复制到</li>
        <li>重命名</li>
        <li>删除</li>
      </ul>
    </div> -->
    <!--复制到 弹出框-->
    <div class="select_file" id="copy_file">
      <div class="folder_tree"><ul id="showtree" class="ztree"></ul></div>
      <div class="button_box">
        <button class="btnCancel fr btn btn-default">取消</button>
        <button class="btnSub fr btn btn-primary mr10" id="copySub">确定</button>
      </div>
    </div>

    <!--移动到 弹出框-->
    <div class="select_file" id="remove_file">
      <div class="folder_tree"><ul id="showremovetree" class="ztree"></ul></div>
      <div class="button_box">
        <button class="btnCancel fr btn btn-default">取消</button>
        <button class="btnSub fr btn btn-primary mr10" id="removeSub">确定</button>
      </div>
    </div>
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="<?php echo SITE_URL; ?>res/js/jquery.min.js"></script>
    <script src="<?php echo SITE_URL; ?>res/js/index.js"></script>
    <script src="<?php echo SITE_URL; ?>res/js/Dialog_jquery.js"></script>
    <script src="<?php echo SITE_URL; ?>res/js/bootstrap.min.js"></script>
    <script src="<?php echo SITE_URL; ?>res/js/jquery.ztree.core.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="<?php echo SITE_URL; ?>res/assets/js/ie10-viewport-bug-workaround.js"></script>
  </body>
</html>
