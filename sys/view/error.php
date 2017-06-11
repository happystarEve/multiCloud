<?php include 'header.php'; ?>

<div class="header">
	<div class="subnav">
		<a href="<?php echo SITE_URL; ?>"><i class="fa fa-home"></i> 首页</a>
		<i class="fa fa-angle-double-right"></i> <a href="">404</a>
		</div>
</div>

<p style="margin:100px 0 30px 0; text-align:center; font-size:18px; font-weight:bold">
<?php echo $errmsg; ?>
</p>
<p style="margin:30px 0 100px 0; text-align:center; font-size:14px;">
<a style="color:#777;text-decoration:none" href="<?php echo SITE_URL; ?>"><i class="fa fa-home"></i> 回首页</a>
</p>

<?php include 'footer.php'; ?>