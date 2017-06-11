<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1">
  <meta name="renderer" content="webkit">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>登录</title>  
  <link rel="stylesheet" href="<?php echo SITE_URL; ?>res/css/login.css">
  <link rel="stylesheet" href="<?php echo SITE_URL; ?>res/font-awesome-4.5.0/css/font-awesome.css">
</head>
<body>


<form action="" method="post">
<div class="login">
  <div class="head">多云存储</div>
  <div class="group">
    <span><i class="fa fa-user"></i></span>
    <input type="text" name="userName" value="<?=isset($_POST['userName'])?htmlspecialchars($_POST['userName']):''?>" placeholder="用户名" autocomplete="off">
  </div>
  <div class="group">
    <span><i class="fa fa-lock"></i></span>
    <input type="password" name="pass" value="<?=isset($_POST['pass'])?htmlspecialchars($_POST['pass']):''?>" placeholder="密码" autocomplete="off">
  </div>
  <div class="group chaptcha">
    <span><img id="captcha_img" onclick="document.getElementById('captcha_img').src='<?php echo SITE_URL; ?>verify'" src="<?php echo SITE_URL; ?>verify" alt="验证码" /></span>
    <input type="text" name="chaptcha" placeholder="验证码" autocomplete="off">
  </div>
  <div class="foot">
    <button type="submit" class="btn"><i class="fa fa-sign-in"></i> 登 录</button>
  </div>
</div>
</form>

<script src="<?php echo SITE_URL; ?>res/js/jquery.min.js"></script>

<script language="javascript">
<?php if (!empty($errmsg)){ echo 'alert("'.$errmsg.'");'; } ?>
</script>
</body>
</html>