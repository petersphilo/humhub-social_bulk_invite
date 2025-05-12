<?php

/**
 * Peter Zieseniss
 * Copyright (C) 2025
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 */

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
		Social Bulk Invite
	</div>
	<div class="panel-body">
		<div style='float: right; '>
			<a id="h477702w55" class="pull-right btn-sm btn btn-default" href="/admin/pending-registrations"><?php echo Yii::t('SocialBulkInviteModule.base','Pending Registrations List'); ?> <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
			<br><br>
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
				$myLangTextDefault=Yii::t('SocialBulkInviteModule.base','This User\'s Current Language');
				$myLangTextEN=Yii::t('SocialBulkInviteModule.base','English');
				$myLangTextFR=Yii::t('SocialBulkInviteModule.base','French');
				$myLangTextDE=Yii::t('SocialBulkInviteModule.base','German');
				$myLangTextES=Yii::t('SocialBulkInviteModule.base','Spanish');
				$mySubmitButton=Yii::t('SocialBulkInviteModule.base','Save (& add to list)');
				echo $form->field($model, 'theSpace')->dropdownList($MySpacesFull); 
				echo $form->field($model, 'theInviteLang')->dropdownList(['App'=>$myLangTextDefault,'en-US'=>$myLangTextEN,'fr-FR'=>$myLangTextFR,'es-ES'=>$myLangTextES,'de-DE'=>$myLangTextDE]);  
				echo $form->field($model, 'theSendRate')->textInput(); 
				//echo $form->field($model, 'showDebug')->checkbox();
			?>
			<span class='mySmallerText myEffectiveSendRateCont'><?php echo Yii::t('SocialBulkInviteModule.base','Approximate Send Rate'); ?>: <span class='myEffectiveSendRate'></span>.</span>
		</div>
		<span id='MyCurrentGetURL'></span>
		<span id='MyNewGetURL'></span>
		
		<br>
		<?php
			echo $form->field($model, 'theInvitees')->textarea();
			echo '<br>'.$ReadTheInvitees; 
		?>
		<br>

		<?php echo Html::submitButton($mySubmitButton, ['class' => 'btn btn-primary']); ?>
		
		<a class="btn btn-default" href="<?php echo Url::to(['/social_bulk_invite/config/config']); ?>">
			<?php echo Yii::t('SocialBulkInviteModule.base','Refresh'); ?>
		</a>
		
		<a class="btn btn-info" href="<?php echo Url::to(['/social_bulk_invite/config/config?resendCurrentInvites=resendCurrentInvites']); ?>">
			<?php echo Yii::t('SocialBulkInviteModule.base','Re-Send All Current Invites'); ?>
		</a>
		<!--
		<a class="btn btn-danger" style='float:right; ' href="<?php echo Url::to(['/social_bulk_invite/config/config?remove=remove']); ?>">
			<?php echo Yii::t('SocialBulkInviteModule.base','Reset Current Invites'); ?>
		</a>
		
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
	$GetPendingInvitesList_cmd=Yii::$app->db->createCommand("SELECT invite_email,invite_queued,invite_exists,member_id,full_member,date_created,date_updated,times_sent 
			FROM social_bulk_invite WHERE full_member=0 ORDER BY id desc;")->queryAll(); 
	$PendingInvitesListCount=count($GetPendingInvitesList_cmd); 
	
	$GetMatchingUserStuff_cmd=Yii::$app->db->createCommand("SELECT token AS theMatchingToken, language AS theMatchingLang FROM user_invite WHERE email=:Email;"); 
	
?>
		<style>
			table.MyRecentInvites{width:100%; }
			.MyRecentInvites tr:first-of-type td{font-weight:500; background-color:#777; color:#fff; }
			.MyRecentInvites td{border:1px solid #ddd; padding:0.25em; }
			.mySmallerText {font-size: 0.8em; }
			.NoWrapLines {white-space: nowrap; }
			.redAlertOnZero, .redAlertOnZero td {background-color:#e99; }
			.myCopyDataLink {position: relative; }
			.myCopied{display: none; background-color:#333; color: #fff; border: 1px solid #ccc; border-radius: 4px; position: absolute; padding: 0.5em 1em; bottom: -2em; right: 2.5em; z-index:99; }
		</style>
		<span style='float:right;'><?php echo Yii::t('SocialBulkInviteModule.base','Invites Not Converted: ').$PendingInvitesListCount; ?></span>
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
				<td>
					<?php echo Yii::t('SocialBulkInviteModule.base','Lang'); ?>
				</td>
				<td class='NoWrapLines'>
					<?php echo Yii::t('SocialBulkInviteModule.base','Date Created'); ?>
				</td>
				<td class='NoWrapLines'>
					<?php echo Yii::t('SocialBulkInviteModule.base','Times Sent'); ?>
				</td>
				<td class='NoWrapLines'>
					<?php echo Yii::t('SocialBulkInviteModule.base','Token'); ?> <span class='mySmallerText'>(<?php echo Yii::t('SocialBulkInviteModule.base','Copy link'); ?>)</span>
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
		$theMatchingToken=''; $theMatchingLang=''; $theMatchingTokenURL=''; $theMatchingTokenText=''; 
		if($PendingInvitesList_row['invite_queued']==1 && $PendingInvitesList_row['invite_exists']==1){
			$GetMatchingUserStuff=$GetMatchingUserStuff_cmd->bindValue(':Email',$PendingInvitesList_row['invite_email'])->queryOne(); 
			if($GetMatchingUserStuff){
				$theMatchingToken=$GetMatchingUserStuff['theMatchingToken']; 
				$theMatchingLang=$GetMatchingUserStuff['theMatchingLang']; 
				$theMatchingTokenURL=Url::to(['/user/registration?token='.$theMatchingToken], true); 
				}
			}
		if($theMatchingToken!=''){
			$theMatchingTokenText=$theMatchingToken.' 
					<div class="pull-right btn-sm btn btn-default myCopyDataLink" my-link="'.$theMatchingTokenURL.'">
						<i class="fa fa-copy" aria-hidden="true"></i>
						<div class="myCopied"><i>Copied!</i><br><span class=\'mySmallerText\'>'.$theMatchingTokenURL.'</span></div>
					</div>'; 
			}
		$BuildTableRow="<tr class='".redAlertOnZero($PendingInvitesList_row['invite_queued'])."'>"
			."<td class='NoWrapLines'>".$PendingInvitesList_row['invite_email'].'</td>'
			."<td>".zeroOneToYN($PendingInvitesList_row['invite_queued']).'</td>'
			.'<td>'.zeroOneToYN($PendingInvitesList_row['invite_exists']).'</td>'
			.'<td>'.$theMatchingLang.'</td>'
			."<td class='NoWrapLines'>".substr($PendingInvitesList_row['date_created'],0,10).'</td>'
			."<td class='NoWrapLines'>".$PendingInvitesList_row['times_sent'].'</td>'
			."<td class='NoWrapLines'>".$theMatchingTokenText.'</td>'
			.'</tr>'; 
		echo $BuildTableRow; 
		}
	
?>
		</table>
	</div>
</div>
<script <?php echo humhub\libs\Html::nonce(); ?>>
	$(function(){
		var MyNewGetURL='', MyCopyDataLink='', myEffectiveSendRateSpan=$('.myEffectiveSendRate'), mySendDelayVal=1, myEffectiveSendRateVal='', mySendDelayForm=$('#configureform-thesendrate'), 
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
		$('.myCopyDataLink').on('click',function(){
			MyCopyDataLink=$(this).attr('my-link'); 
			navigator.clipboard.writeText(MyCopyDataLink); 
			$(this).children('.myCopied').fadeIn(1200,function(){
				$(this).fadeOut(1600);
				}); 
			}); 
		mySendDelayForm.after($('.myEffectiveSendRateCont'));
		calcMyEffectiveSendRate=function(){
			mySendDelayVal=mySendDelayForm.val(); 
			console.log(mySendDelayVal); 
			if(mySendDelayVal>0&&mySendDelayVal<3600){
				myEffectiveSendRateVal=Math.ceil(3600/mySendDelayVal)+"/h"; 
				}
			else if(mySendDelayVal>3600){
				myEffectiveSendRateVal=(Math.ceil(864000/mySendDelayVal)/10)+"/<?php echo Yii::t('SocialBulkInviteModule.base','day'); ?>"; 
				}
			myEffectiveSendRateSpan.text(myEffectiveSendRateVal); 
			}
		mySendDelayForm.on('change blur mouseenter mouseleave keyup',calcMyEffectiveSendRate); 
		calcMyEffectiveSendRate();
		}); 
</script>
