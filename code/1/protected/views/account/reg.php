<?php 
use benben\web\ClientScript;
use benben\Benben;
use benben\web\helpers\Html;
use benben\web\helpers\JavaScriptExpression;
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>管理员登录</title>
<?php 
$cs=Benben::app()->clientScript;
$cs->coreScriptPosition=ClientScript::POS_HEAD;
$baseUrl=$this->owner->module->assetsUrl;
$cs->registerCoreScript('jquery');
$cs->registerScriptFile($baseUrl.'public/js/jquery.tooltip-1.2.6.min.js');
?>
<script type="text/javascript">
function a(form){
	alert("ccccc");
	return true;
}
</script>
</head>

<body>
<?php $form = $this->beginWidget('benben\\web\\widgets\\ActiveForm', array(
		'id'=>'user-form',
		'enableAjaxValidation'=>true,
		'enableClientValidation'=>true,
		'clientOptions'=>array(
			'validateOnSubmit'=>true,
			'beforeValidate'=>(new JavaScriptExpression("a")),
		),
		'focus'=>array($model,'firstName'),
  )); ?>
 
  <?php echo $form->errorSummary($model); ?>
 
  <div class="row">
      <?php echo $form->labelEx($model,'username'); ?>
      <?php echo $form->textField($model,'username'); ?>
      <?php echo $form->error($model,'username'); ?>
  </div>
  <div class="row">
      <?php echo $form->labelEx($model,'password'); ?>
      <?php echo $form->textField($model,'password'); ?>
      <?php echo $form->error($model,'password'); ?>
  </div>
  <div class="row">
      <?php echo $form->labelEx($model,'password2'); ?>
      <?php echo $form->textField($model,'password2'); ?>
      <?php echo $form->error($model,'password2'); ?>
  </div>
  <div class="row">
      <?php echo $form->labelEx($model,'email'); ?>
      <?php echo $form->textField($model,'email'); ?>
      <?php echo $form->error($model,'email'); ?>
  </div>
  <div class="row">
 	<?php echo Html::submitButton('Enter'); ?>
  </div>
  <?php $this->endWidget(); ?>
</body>
</html>
