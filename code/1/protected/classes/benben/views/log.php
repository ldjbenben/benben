<?php 
use benben\log\Logger;
?>
<!-- start log messages -->
<table class="benbenLog" width="100%" cellpadding="4" style="border-spacing:2px;font:12px Verdana, Arial, Helvetica, sans-serif;background:#AEAEAE;color:#666666;">
	<tr>
		<th style="background:black;color:white;" colspan="5">
			Application Log
		</th>
	</tr>
	<tr style="background-color: #ccc;">
	    <th style="width:120px">Timestamp</th>
		<th>Level</th>
		<th>Category</th>
		<th>Message</th>
	</tr>
<?php
$colors=array(
	Logger::LEVEL_TRACE=>'#DFFFE0',
	Logger::LEVEL_INFO=>'#FFFFDF',
	Logger::LEVEL_WARNING=>'#FFDFE5',
	Logger::LEVEL_ERROR=>'#FFC0CB',
);
foreach($data as $index=>$log)
{
	$color=($index%2)?'#F5F5F5':'#FFFFFF';
	if(isset($colors[$log['level']]))
		$color=$colors[$log['level']];
	$time=date('H:i:s.',$log['timestamp']).sprintf('%06d',(int)(($log['timestamp']-(int)$log['timestamp'])*1000000));
?>
<tr style="background:<?php echo $color;?>;">
	<td align="center"><?php echo $time;?></td>
	<td><?php echo $log['level'];?></td>
	<td><?php echo $log['category'];?></td>
	<td>
	<?php echo wordwrap($log['msg']);?>
	<?php
	if (isset($log['addtional']['files'])):
	    foreach($log['addtional']['files'] as $option):
	 ?>
	<div><?php echo $option['file'].' line:'.$option['line'];?></div>
	<?php 
	    endforeach;
	endif;
	?>
	</td>
</tr>
<?php 
}
?>
</table>
<!-- end of log messages -->