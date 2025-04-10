<?php

/* use Yii; */

use humhub\modules\ui\form\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;

use yii\db; 

use humhub\models\Setting;

/**
 * @var $model \humhub\modules\social_bulk_invite\models\ConfigureForm
 */

$social_bulk_invite=Yii::$app->getModule('social_bulk_invite'); 

$ReadTheSpace=$social_bulk_invite->settings->get('theSpace'); 

$ReadTheInvitees=$social_bulk_invite->settings->get('theInvitees'); 
//$ReadTheSaveCount=$social_bulk_invite->settings->get('theSaveCount'); 
$showDebugYN=$social_bulk_invite->settings->get('showDebug'); 

$GetTheSpaceName_cmd=Yii::$app->db->createCommand("SELECT name FROM space WHERE id=$ReadTheSpace;")->queryScalar(); 

$MySpacesFull=[]; 
$ListAllSpaces_cmd=Yii::$app->db->createCommand("SELECT id,name FROM space;")->queryAll(); 
foreach($ListAllSpaces_cmd as $ListAllSpaces_row){
	$SpaceName=$ListAllSpaces_row['id'].' -- '.$ListAllSpaces_row['name']; 
	$MySpacesFull+=[$ListAllSpaces_row['id']=>$SpaceName]; 
	}

?>

<div class="panel panel-default">
	<div class="panel-heading">
		Social Bulk Invite Module Configuration
	</div>
	<div class="panel-body">
		<div style='float: right; '>
			<span class="btn btn-info btn-sm" id='socBulkInvite-DL'><?php echo Yii::t('SocialBulkInviteModule.base','Download Bulk Invite List'); ?></span>
		</div>
		<p>
			<?php 
				/* echo Yii::t('SocialBulkInviteModule.base','The Current Selected Groups are').": <strong>$ConcernedGroups</strong><br>";  */
				echo Yii::t('SocialBulkInviteModule.base','The Current Selected Space is').": <strong>$GetTheSpaceName_cmd</strong><br>"; 
			?>
		</p>
		<br/>

		<?php $form = ActiveForm::begin(); ?>

		<div class="form-group">
			<?php 
				echo $form->field($model, 'theSpace')->dropdownList($MySpacesFull); 
				echo $form->field($model, 'theInviteLang')->dropdownList(['en-US'=>'EN','fr-FR'=>'FR']);  
				echo $form->field($model, 'showDebug')->checkbox();
				echo $form->field($model, 'theSendRate')->textInput(); 
			?>
		</div>
		<span id='MyCurrentGetURL'></span>
		<span id='MyNewGetURL'></span>
		
		<br>
		<?php
			echo $form->field($model, 'theInvitees')->textarea();
			echo '<br>'.$ReadTheInvitees; 
		?>
		<br>

		<?php echo Html::submitButton('Save', ['class' => 'btn btn-primary']); ?>
		
		<a class="btn btn-default" href="<?php echo Url::to(['/social_bulk_invite/config/config']); ?>">
			<?php echo Yii::t('SocialBulkInviteModule.base','Refresh'); ?>
		</a>
		
		<a class="btn btn-danger" style='float:right; ' href="<?php echo Url::to(['/social_bulk_invite/config/config?remove=remove']); ?>">
			<?php echo Yii::t('SocialBulkInviteModule.base','Reset Current Invites'); ?>
		</a>
		<!--
		<a class="btn btn-danger" style='float:right; ' href="<?php echo Url::to(['/social_bulk_invite/config/config?reset=reset']); ?>">
			Reset/Refresh
		</a>
		-->
		<?php $form::end(); ?>
		<?php
			if($showDebugYN==1){
				$myshowMessage=''; 
				if(isset($_GET['showMessage'])){$myshowMessage.=$_GET['showMessage']; }else{$myshowMessage=''; }
				if($directShowMessage!=''){$myshowMessage.=$directShowMessage; }
				echo '<br>Debug Log:<br>'.$myshowMessage; 
				}
		?>
	</div>
	<div class="panel-body">
<?php			
	$GetPendingInvitesList_cmd=Yii::$app->db->createCommand("SELECT invite_email,invite_queued,invite_exists,member_id,full_member,date_created,date_updated,times_sent FROM social_bulk_invite WHERE full_member=0 ORDER BY id desc;")->queryAll(); 
	
?>
		<style>
			table.MyRecentInvites{width:100%; }
			.MyRecentInvites tr:first-of-type td{font-weight:500; background-color:#777; color:#fff; }
			.MyRecentInvites td{border:1px solid #ddd; padding:0.25em; }
			.NoWrapLines {white-space: nowrap; }
			.redAlertOnZero, .redAlertOnZero td {background-color:#e99; }
		</style>
		<table class='MyRecentInvites'>
			<tr>
				<td class='NoWrapLines'>
					<?php echo Yii::t('SocialBulkInviteModule.base','eMail'); ?>
				</td>
				<td class='NoWrapLines'>
					<?php echo Yii::t('SocialBulkInviteModule.base','Invite Queued?'); ?>
				</td>
				<td>
					<?php echo Yii::t('SocialBulkInviteModule.base','Invite Sent?'); ?>
				</td>
				<td class='NoWrapLines'>
					<?php echo Yii::t('SocialBulkInviteModule.base','Date Created'); ?>
				</td>
			</tr>
<?php			
	function zeroOneToYN($zoyn){
		$resp=Yii::t('SocialBulkInviteModule.base','No');
		if($zoyn==1){$resp=Yii::t('SocialBulkInviteModule.base','Yes');}
		return $resp;}
	function redAlertOnZero($raoz){
		$resp='';
		if($raoz==0){$resp='redAlertOnZero ';}
		return $resp;}
	foreach($GetPendingInvitesList_cmd as $PendingInvitesList_row){
		$BuildTableRow="<tr class='".redAlertOnZero($PendingInvitesList_row['invite_queued'])."'>"
			."<td class='NoWrapLines'>".$PendingInvitesList_row['invite_email'].'</td>'
			."<td>".zeroOneToYN($PendingInvitesList_row['invite_queued']).'</td>'
			.'<td>'.zeroOneToYN($PendingInvitesList_row['invite_exists']).'</td>'
			."<td class='NoWrapLines'>".substr($PendingInvitesList_row['date_created'],0,10).'</td>'
			.'</tr>'; 
		echo $BuildTableRow; 
		}
	
?>
		</table>
	</div>
</div>
<script <?php echo humhub\libs\Html::nonce(); ?>>
	$(function(){
		var MyNewGetURL='',
			MyCurrentGetURL=window.location.search; 
		$('#socBulkInvite-DL').on('click',function(){
			if(MyCurrentGetURL.length){MyNewGetURL=MyCurrentGetURL+'&SocBulkInviteDL=Yes'; }
			else{MyNewGetURL='?SocBulkInviteDL=Yes'; }
			/* $('#MyCurrentGetURL').text(MyCurrentGetURL);  */
			/* $('#MyNewGetURL').text(MyNewGetURL);  */
			fetch(MyNewGetURL)
				.then(resp => resp.blob())
				.then(blob => {
					var Myurl = window.URL.createObjectURL(blob);
					const TempdlLink = document.createElement('a');
					TempdlLink.style.display = 'none';
					TempdlLink.href = Myurl;
					TempdlLink.download = 'SocialBulkInviteList.csv';
					document.body.appendChild(TempdlLink);
					TempdlLink.click();
					window.URL.revokeObjectURL(Myurl);
					TempdlLink.remove();
					})
				.catch(() => alert('something went wrong..'));
			}); 
		}); 
</script>
