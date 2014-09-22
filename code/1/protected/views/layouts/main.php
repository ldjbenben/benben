<?php 
use benben\Benben;
$assetsUrl = Benben::app()->getAssetsUrl();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>无标题文档</title>
<link rel="stylesheet" type="text/css" href="<?php echo $assetsUrl;?>/css/main.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $assetsUrl;?>/css/form.css" />
</head>

<body>
<div id="main" class="simple-main">
<header></header>
<?php echo $content;?></div>
<script type="text/javascript" src="<?php echo $assetsUrl;?>/js/common.js"></script>
</body>
</html>
