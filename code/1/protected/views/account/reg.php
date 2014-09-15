<?php 
use benben\web\ClientScript;
use benben\Benben;
use benben\web\helpers\Html;
use benben\web\helpers\JavaScriptExpression;
?>

<?php 
$cs=Benben::app()->clientScript;
$cs->coreScriptPosition=ClientScript::POS_HEAD;
$baseUrl=$this->owner->module->assetsUrl;
$cs->registerCoreScript('jquery');
$cs->registerScriptFile($baseUrl.'public/js/jquery.tooltip-1.2.6.min.js');

?>
	<div class="register-process">
    	<div class="process first-process process-current">
        	<span class="index">1</span>填写注册信息
            <span class="process-dir-outside"></span>
        </div>
        <div class="process">
	        <span class="process-dir-inside"></span>
        	<span class="index">2</span>邮箱激活
            <span class="process-dir-outside"></span>
        </div>
        <div class="process">
        <span class="process-dir-inside"></span>
        	<span class="index">3</span>注册成功

        </div>
    </div>
    <div class="register-info">
    	<h3 class="title">注册帐号</h3>
        <div class="register-form">
<?php
        $form = $this->beginWidget('benben\\web\\widgets\\ActiveForm', array(
		'id'=>'user-form',
		'enableAjaxValidation'=>true,
		'enableClientValidation'=>true,
		'clientOptions'=>array(
				'validateOnSubmit'=>true,
				'afterValidateAttribute'=>(new JavaScriptExpression('afterValidateAttribute')),
		),
		'focus'=>array($model,'username'),
));
?>
                <div class="item">
                    <label>用户名</label>
                    <?php echo $form->textField($model,'username', array('class'=>'input-txt username')); ?>
                    <div class="tooltip-info">
	                    <span class="icon-border"></span>
	                    <span class="icon-bg"></span>
	                    <span class="state"></span>
	                    <div class="mess"><?php echo $form->error($model,'username'); ?></div>
                    </div>
                </div>
                <div class="item">
                    <label>登录邮箱</label>
                    <?php echo $form->textField($model,'email', array('class'=>'input-txt email')); ?>
                    <div class="tooltip-info">
	                    <span class="icon-border"></span>
	                    <span class="icon-bg"></span>
	                    <span class="state"></span>
	                    <div class="mess"><?php echo $form->error($model,'email'); ?></div>
                    </div>
                </div>
                <div class="item">
                    <label>登录密码</label>
                    <?php echo $form->passwordField($model,'password', array('class'=>'input-txt password', 'onkeyup'=>'onPasswordKeyUp()', 'onfocus'=>'passwordOnFocus()', 'onblur'=>'passwordOnBlur()')); ?>
                    <div class="tooltip-info password-tip" id="passwordTip">
	                    <span class="icon-border"></span>
	                    <span class="icon-bg"></span>
	                    <span class="state"></span>
	                    <span class="strength">
	                    	<em class="level">低</em>
	                    	<span></span><span></span><span></span></span>
	                    <div class="mess">6-20个字符；只能包含大小写、数字及标点</div>
                    </div>
                    <div class="tooltip-info" id="passwordErrorMessage">
	                    <span class="icon-border"></span>
	                    <span class="icon-bg"></span>
	                    <span class="state"></span>
	                    <div class="mess"><div class="errorMessage"></div></div>
                    </div>
                </div>
                <div class="item item-password2">
                    <label>密码确认</label>
                    <?php echo $form->passwordField($model,'password2', array('class'=>'input-txt password2', 'onblur'=>'password2OnBlur()')); ?>
                    <div class="tooltip-info">
	                    <span class="icon-border"></span>
	                    <span class="icon-bg"></span>
	                    <span class="state"></span>
	                    <div class="mess"><div class="errorMessage"></div></div>
                    </div>
                </div>
                <div class="item">
                    <label>验证码</label>
                    <input type="text" class="input-txt verify" />
                    <img src="<?php echo $benben['assetsUrl'];?>/img/verifyhandler.png" height="34" class="verify" />
                    <a href="#" class="change-code">看不清？换一张</a>
                </div>
                <div class="item provision-item">
                	<input type="checkbox" name="" /> 我已经仔细阅读并接受
                    <a href="#">注册条款</a>
                </div>
                 <div class="item next-btn-item">
                 	<a type="button" class="next-step">下一步</a>
                 </div>
<?php $this->endWidget(); ?>
        </div>
    </div>

<script type="text/javascript">
function password2OnBlur()
{
	if(jQuery("#RegFormModel_password2").val() != jQuery("#RegFormModel_password").val())
	{
		jQuery(".item-password2").addClass("error");
		jQuery(".item-password2 .errorMessage").html("两次输入的密码不一致");
	}
	else
	{
		jQuery(".item-password2").removeClass("error");
		jQuery(".item-password2").addClass("success");
	}
}

function passwordOnFocus()
{
	jQuery("#passwordErrorMessage").hide();
	jQuery("#passwordTip").show();
}

function passwordOnBlur()
{
	jQuery("#passwordTip").hide();
}

function onPasswordKeyUp()
{
	var pwd = jQuery("#RegFormModel_password").val();
	var length = pwd.length;
	var strLevel = ["低","中","高"];
	var level = checkSecurity(pwd);
	
	if(length < 6)
	{
		level = -1;
		jQuery("#passwordErrorMessage .errorMessage").html("密码长度不能少于6个字符");
	}
	else
	{
		jQuery("#RegFormModel_password").parent().removeClass("error");
		jQuery("#RegFormModel_password").parent().addClass("success");
	}
	
	jQuery("#passwordTip .level").html(strLevel[level]);
	jQuery("#passwordTip .strength span").removeClass("on");

	jQuery("#passwordTip .strength span:lt("+(level+1)+")").addClass("on");
}

function afterValidateAttribute(form, attribute, data, hasError)
{
	/*
	if(hasError)
	{
		jQuery("#"+attribute.id).siblings(".tooltip-info").removeClass("tooltip-ok");
		jQuery("#"+attribute.id).siblings(".tooltip-info").show();
	}
	else
	{
		jQuery("#"+attribute.id).siblings(".tooltip-info").addClass("tooltip-ok");
	}
	*/
}

function checkSecurity(s)
{
	if(s.length>15)
	{
		return 2;
	}
	else if(s.length>10)
	{
		return 1;
	}
	else
	{
		return 0;
	}
}
</script>