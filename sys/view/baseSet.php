<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="Eve">

    <title>多云存储基本设置</title>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>res/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>res/font-awesome-4.5.0/css/font-awesome.css">
    <!--[if IE 7]>
    <link rel="stylesheet" href="assets/css/font-awesome-ie7.min.css">
    <![endif]-->

    <!-- Custom styles for this template -->
    <link href="<?php echo SITE_URL; ?>res/css/index.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>res/css/common.css"  rel="stylesheet">

    <!-- Just for debugging purposes. Don't actually copy these 2 lines! -->
    <!--[if lt IE 9]><script src="<?php echo SITE_URL; ?>assets/js/ie8-responsive-file-warning.js"></script><![endif]-->
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
          <form class="navbar-form navbar-right" method="GET" action="<?php echo SITE_URL; ?>">
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
            <li><a class="nav-a" href="<?php echo SITE_URL; ?>"><i class="fa fa-home"></i>全部文件<span class="sr-only">(current)</span></a></li>
            <li><a class="nav-a" href="<?php echo SITE_URL; ?>safeset"><i class="fa fa-lock"></i>安全策略</a></li>
            <li class="active"><a class="nav-a" href="<?php echo SITE_URL; ?>baseset"><i class="fa fa-cogs"></i>基本设置</a></li>
          </ul>
        </div>
        <!--侧边菜单栏结束-->

        <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
          <div class="well bgwhite">
            <p><strong>用户密钥：<?php echo $userSecretDecrypted; ?></strong></p>
            <p class="deepyellow"><small><em>密钥功能：为上传的文件加密，无法更改，若忘记密码及密钥，数据将无法找回。</em></small></p>
          </div>

          <div class="file_detail">
            <h3 class="text-primary ml20">修改密码</h3>
            <form class="form-horizontal mt20" role="form" method="POST" action="">
              <div class="form-group">
                <label for="oldPassword" class="col-sm-2 control-label">原始密码</label>
                <div class="col-sm-6"><input type="password" name="oldPassword" class="form-control" id="oldPassword" placeholder="原始密码"></div>              
              </div>
              <div class="form-group">
                <label for="newPassword1" class="col-sm-2 control-label">新设密码</label>
                <div class="col-sm-6"><input type="password" name="newPassword1" class="form-control" id="newPassword1" placeholder="新设密码"></div>
              </div>
              <div class="form-group">
                <label for="newPassword2" class="col-sm-2 control-label">确认密码</label>
                <div class="col-sm-6"><input type="password" name="newPassword2" class="form-control" id="newPassword2" placeholder="确认密码"></div>
              </div>
              <div class="form-group mt30">
                <div class="col-sm-offset-2 col-sm-10">
                  <button type="submit" class="btn btn-primary">修改</button>
                </div>
              </div>
            </form>
          </div>
            
        </div>
      </div>
    </div>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="<?php echo SITE_URL; ?>res/js/jquery.min.js"></script>
    <script src="<?php echo SITE_URL; ?>res/js/index.js"></script>
    <script src="<?php echo SITE_URL; ?>res/js/bootstrap.min.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="<?php echo SITE_URL; ?>res/assets/js/ie10-viewport-bug-workaround.js"></script>
  </body>
</html>
