<?php 
use benben\web\ClientScript;
use benben\Benben;

$cs=Benben::app()->clientScript;
$cs->coreScriptPosition=ClientScript::POS_HEAD;
$cs->registerCoreScript('jquery');
$assetsUrl = Benben::app()->getAssetsUrl();
?>
<div class="login-wrap">
 	<div class="login-banner"><img src="<?php echo $assetsUrl.'/img/login-banner.png';?>" /></div>
    <div class="login-info">
    	<h3 class="title">帐号登录</h3>
        <div class="form login-form">
<?php
        $form = $this->beginWidget('benben\\web\\widgets\\ActiveForm', array(
		'id'=>'login-form',
		'enableClientValidation'=>false,
		'focus'=>array($model,'username'),
));
?>
	<div class="item error-message"><?php echo $form->error($model,'password'); ?></div>
	<div class="item">
                    <?php echo $form->textField($model,'username', array('class'=>'input-txt username','placeholder'=>'输入用户名/邮箱')); ?>
                </div>
                <div class="item item-password">
                    <?php echo $form->passwordField($model,'password', array('class'=>'input-txt password','placeholder'=>'输入密码')); ?>
                </div>
                <div class="item item-remember-me">
                	<input type="checkbox" name="" /> 下次自动登录
                    <a class="link link-register" href="<?php echo Benben::app()->createUrl('account/reg');?>">注册帐号</a>
                    <a class="link link-forget-password" href="#">忘记密码</a>
                </div>
                 <div class="item next-btn-item">
                 	<input type="submit" class="btn btn-login" value="登录" />
                 </div>
<?php $this->endWidget(); ?>      
</div>
</div>
</div>
