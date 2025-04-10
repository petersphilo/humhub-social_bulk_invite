<?php

namespace humhub\modules\social_bulk_invite\controllers;

use Yii;
use yii\console\Controller;
use yii\web\Request;
use humhub\modules\social_bulk_invite\models\ConfigureForm;
use humhub\models\Setting;
use yii\helpers\Json;

use humhub\modules\user\components\ActiveQueryUser;
use humhub\modules\user\models\Invite;
use humhub\modules\user\models\User;

use yii\base\Behavior;
use yii\base\Exception;
use yii\validators\EmailValidator;

use yii\db; 
use yii\db\Query; 
use yii\db\Command; 

use humhub\modules\social_bulk_invite\jobs\InviteJob;

/**
 * Defines the configure actions.
 *
 * @author Peter Zieseniss
 */
class ConfigController extends \humhub\modules\admin\components\Controller {
	
	public function behaviors(){
		return [
			'acl' => [
				'class' => \humhub\components\behaviors\AccessControl::className(),
				'adminOnly' => true
				]
			];
		}
	
	/**
	 * Configuration Action for Super Admins
	 */
	
	public function actionConfig(){
		if(Yii::$app->request->get('SocBulkInviteDL')){$this->MyDataRequest(); }
		else{
			$showMessage=''; $takeABreather=''; 
			$social_bulk_invite=Yii::$app->getModule('social_bulk_invite'); 
			if(Yii::$app->request->get('reset')=='reset'){
				$social_bulk_invite->settings->set('theInvitees', '');
				//$social_bulk_invite->settings->set('theSaveCount', 0);
				$social_bulk_invite->settings->set('theSendRate', 4);
				$social_bulk_invite->settings->set('theInviteLang', 'en-US');
				$social_bulk_invite->settings->set('showDebug', 0);
				$takeABreather='takeABreather'; 
				}
			if(Yii::$app->request->get('remove')=='remove'){
				$showMessage.=$this->myRemoveCurrentInvites(); 
				$takeABreather='takeABreather'; 
				}
			$form = new ConfigureForm();
			$form->theSpace = $social_bulk_invite->settings->get('theSpace');
			$form->theInvitees = $social_bulk_invite->settings->get('theInvitees');
			$form->theSendRate = $social_bulk_invite->settings->get('theSendRate');
			$form->theInviteLang = $social_bulk_invite->settings->get('theInviteLang');
			$form->showDebug = $social_bulk_invite->settings->get('showDebug');
			
			//$mySaveCount = $social_bulk_invite->settings->get('theSaveCount'); 
			$customData=''; 
			//$form->theInvitees = ''; 
			if ($form->load(Yii::$app->request->post()) && $form->validate()) {
				$form->theSpace = $social_bulk_invite->settings->set('theSpace', $form->theSpace);
				//$form->theInvitees = $social_bulk_invite->settings->set('theInvitees', $form->theInvitees);
				$form->theSendRate = $social_bulk_invite->settings->set('theSendRate', $form->theSendRate);
				$form->theInviteLang = $social_bulk_invite->settings->set('theInviteLang', $form->theInviteLang);
				$form->showDebug = $social_bulk_invite->settings->set('showDebug', $form->showDebug);
				
				//$mySaveCount++; 
				$customData='customData'; 
				$showMessage.=$this->mySetNewInvites($form->theInvitees);
				$takeABreather='takeABreather'; 
				//$social_bulk_invite->settings->set('theSaveCount', $mySaveCount); 
				if($social_bulk_invite->settings->get('showDebug')==0){$showMessage='';}
				return $this->redirect(['/social_bulk_invite/config/config','customData'=>$customData,'showMessage'=>$showMessage]);
				}
			if($takeABreather!='takeABreather'){$showMessage.=$this->myReadAndProcessInvites(''); }
			if($social_bulk_invite->settings->get('showDebug')==0){$showMessage='';}
			return $this->render('config', array('model' => $form,'directShowMessage'=>$showMessage,'directCustomData'=>'this works'));
			}
		}
	
	public function myReadAndProcessInvites($command){
		$myActivityLog=''; $myBR='<br>'; 
		$myActivityLog.=$myBR.Yii::t('SocialBulkInviteModule.base','-- begin Process Invites --').$myBR; 
		
		$curUserID=Yii::$app->user->id;
		
		$mysbi=Yii::$app->getModule('social_bulk_invite'); 
		$inviteSpace = $mysbi->settings->get('theSpace'); 
		$inviteSendRate = $mysbi->settings->get('theSendRate'); 
		$inviteLang = $mysbi->settings->get('theInviteLang'); 
		
		$inviteQueueDelay=0; $inviteQueueDelay+=$inviteSendRate; 
		
		$GetPendingInvitesList_cmd=Yii::$app->db->createCommand(
			"SELECT id as socBulkInviteID,invite_email,invite_queued,invite_exists,member_id,full_member,date_created,date_updated,times_sent FROM social_bulk_invite WHERE full_member=0 OR invite_exists=0;")->queryAll(); 
		/*
		$GetPendingInvitesList_cmd=Yii::$app->db->createCommand(
			"SELECT id as socBulkInviteID,invite_email,invite_queued,invite_exists,member_id,full_member,date_created,date_updated,times_sent FROM social_bulk_invite WHERE full_member=0 AND invite_exists=0;")->queryAll(); 
		*/
		if(count($GetPendingInvitesList_cmd)==0){
			$myActivityLog.=Yii::t('SocialBulkInviteModule.base','Nothing to do..').$myBR; 
			return $myActivityLog; 
			}
		
		$CheckHHInvitesByEmail_cmd=Yii::$app->db->createCommand("SELECT id as HHInviteID FROM user_invite WHERE email=:Email;"); 
		
		$CheckHHMemberByEmail_cmd=Yii::$app->db->createCommand("SELECT id as HHMemberID,status FROM user WHERE email=:Email;"); 
		
		$CheckSBIInviteQueued_cmd=Yii::$app->db->createCommand("SELECT id as sbiID FROM social_bulk_invite WHERE (invite_email=:Email AND invite_queued=1);"); 
		
		$CheckSBIInviteQueuedExists_cmd=Yii::$app->db->createCommand("SELECT id as sbiID FROM social_bulk_invite WHERE (invite_email=:Email AND invite_queued=1 AND invite_exists=0);"); 
		
		$RecordNewInvite_cmd=Yii::$app->db->createCommand("UPDATE social_bulk_invite SET invite_queued=1,date_updated=NOW() WHERE invite_email=:Email;");
		
		$RecordExistingInvite_cmd=Yii::$app->db->createCommand("UPDATE social_bulk_invite SET invite_queued=1,invite_exists=1,date_updated=NOW() WHERE invite_email=:Email;");
		
		foreach($GetPendingInvitesList_cmd as $GetPendingInvitesList_row){
			$myInviteEmail=$GetPendingInvitesList_row['invite_email'];
			// first check if member
			$CheckHHMemberByEmail=$CheckHHMemberByEmail_cmd->bindValue(':Email',$myInviteEmail)->queryAll(); 
			$HHMemberByEmailCount=count($CheckHHMemberByEmail); 
			if($HHMemberByEmailCount==1){ // full member, not yet updated, update info do nothing else
				$theMember_id=$CheckHHMemberByEmail[0]['HHMemberID']; 
				Yii::$app->db->createCommand("UPDATE social_bulk_invite SET full_member=1,member_id=$theMember_id,date_updated=NOW() WHERE invite_email='$myInviteEmail';")->query(); 
				$myActivityLog.=$myInviteEmail.Yii::t('SocialBulkInviteModule.base',': full member, not yet updated, update info do nothing else..').$myBR; 
				//return true; 
				}
			elseif($HHMemberByEmailCount>1){ // multiple matches full member, not sure what to do..
				$myActivityLog.=$myInviteEmail.Yii::t('SocialBulkInviteModule.base',': Multiple members with same email!').$myBR; 
				//return false; 
				}
			elseif($HHMemberByEmailCount==0){ // no full member, next step: check invite status
				// now check invite status
				$myActivityLog.=$myInviteEmail.Yii::t('SocialBulkInviteModule.base',': no full member, next step: check invite status..').$myBR; 
				$CheckHHInvitesByEmail=$CheckHHInvitesByEmail_cmd->bindValue(':Email',$myInviteEmail)->queryAll(); 
				$CheckSBIInviteQueued=$CheckSBIInviteQueued_cmd->bindValue(':Email',$myInviteEmail)->queryAll(); 
				$HHInvitesByEmailCount=count($CheckHHInvitesByEmail);
				$SBIInviteQueuedCount=count($CheckSBIInviteQueued);
				if($HHInvitesByEmailCount==1 || $SBIInviteQueuedCount==1){ // HH Invite exists record it here..
					if($SBIInviteQueuedCount==0){
						$myActivityLog.=$myInviteEmail.Yii::t('SocialBulkInviteModule.base',': HH Invite exists record it here..').$myBR; 
						$RecordExistingInvite_cmd->bindValue(':Email',$myInviteEmail)->query(); 
						}
					if($HHInvitesByEmailCount==0){
						$myActivityLog.=$myInviteEmail.Yii::t('SocialBulkInviteModule.base',': Invite recorded here, HH Invite should appear soon..').$myBR; 
						}
					if($HHInvitesByEmailCount==1 && $SBIInviteQueuedCount==1){
						$CheckSBIInviteQueuedExists=$CheckSBIInviteQueuedExists_cmd->bindValue(':Email',$myInviteEmail)->query(); 
						if(count($CheckSBIInviteQueuedExists)==1){
							$myActivityLog.=$myInviteEmail.Yii::t('SocialBulkInviteModule.base',': Invite queued in HH; recording it here..').$myBR; 
							$RecordExistingInvite_cmd->bindValue(':Email',$myInviteEmail)->query(); 
							}
						$myActivityLog.=$myInviteEmail.Yii::t('SocialBulkInviteModule.base',': Invite recorded here and queued in HH; nothing else to do now..').$myBR; 
						}
					}
				elseif($HHInvitesByEmailCount>1 || $SBIInviteQueuedCount>1){ // **multiple** HH Invites exist -- ERROR!
					$myActivityLog.=$myInviteEmail.Yii::t('SocialBulkInviteModule.base',': **multiple** HH Invites exist -- ERROR!..').$myBR; 
					}
				elseif($HHInvitesByEmailCount==0 && $SBIInviteQueuedCount==0){ // no HH invite, proceed to next step: send invite
					$myActivityLog.=$myInviteEmail.Yii::t('SocialBulkInviteModule.base',': no invite yet, proceed to next step: send invite..').$myBR; 
					/* */
					// do Invite
					Yii::$app->queue->delay($inviteQueueDelay)->push(new InviteJob([
						'inviteEmail' => $myInviteEmail,
						'inviteSpace' => $inviteSpace,
						'inviteOrigin' => $curUserID,
						'inviteLanguage' => $inviteLang,
						]));
					$myActivityLog.=$myInviteEmail.Yii::t('SocialBulkInviteModule.base',': new invite queued..').$myBR; 
					// record Invite
					$RecordNewInvite=$RecordNewInvite_cmd->bindValue(':Email',$myInviteEmail)->query(); 
					$myActivityLog.=$myInviteEmail.Yii::t('SocialBulkInviteModule.base',': new invite recorded in db..').$myBR; 
					
					// increase delay
					$inviteQueueDelay+=$inviteSendRate; 
					$myActivityLog.=$myInviteEmail.Yii::t('SocialBulkInviteModule.base',': new invite rate: ').$inviteQueueDelay.'..'.$myBR; 
					}
				else{ // shouldn't exist (HHinvite-step).. do nohing
					$myActivityLog.=$myInviteEmail.Yii::t('SocialBulkInviteModule.base',': shouldn\'t exist (HHinvite-step).. do nohing..').$myBR; 
					}
				}
			else{ // shouldn't exist (member-step).. do nohing..
				$myActivityLog.=$myInviteEmail.Yii::t('SocialBulkInviteModule.base',': shouldn\'t exist (member-step).. do nohing..').$myBR; 
				//return false; 
				}
			
			
			}
		$myActivityLog.=Yii::t('SocialBulkInviteModule.base','-- end Process Invites --').$myBR.$myBR; 
		return $myActivityLog; 
		}
	
	public function mySetNewInvites($fromInput){
		$myActivityLog=''; $myBR='<br>'; 
		$myActivityLog.=$myBR.Yii::t('SocialBulkInviteModule.base','-- begin Set New Invites --').$myBR; 
		
		if($fromInput==''){
			return Yii::t('SocialBulkInviteModule.base','Nothing to do').$myBR; 
			}
		
		$formInputPre=preg_split("/(\n|\r|,|;)/",$fromInput); 
		
		$RecordNewInvites_cmd=Yii::$app->db->createCommand("INSERT INTO social_bulk_invite (invite_email) VALUES (:Email);");
		
		$CheckExistingEmail_cmd=Yii::$app->db->createCommand("SELECT id as sbiID FROM social_bulk_invite WHERE invite_email=:Email;");
		
		foreach($formInputPre as $eachlinePre){
			if($eachlinePre==''){
				$myActivityLog.=$eachline.Yii::t('SocialBulkInviteModule.base','empty line').$myBR; 
				}
			else{
				$illegalChars=array(' ',"'",'"','|');
				$eachline=str_replace($illegalChars,'',$eachlinePre); 
				
				$validator = new EmailValidator;
				if (!$validator->validate($eachline)) {
					$myActivityLog.=$eachline.Yii::t('SocialBulkInviteModule.base',': the email you provided could not be validated').$myBR; 
					return false;
					//return $myActivityLog; 
					}
				$CheckExistingEmail=$CheckExistingEmail_cmd->bindValue(':Email',$eachline)->queryAll(); 
				if(count($CheckExistingEmail)==0){
					$RecordNewInvites=$RecordNewInvites_cmd->bindValue(':Email',$eachline)->query(); 
					$myActivityLog.=$eachline.Yii::t('SocialBulkInviteModule.base',': added to db').$myBR; 
					}
				else{
					$myActivityLog.=$eachline.Yii::t('SocialBulkInviteModule.base',': already existed, nothing to do').$myBR; 
					}
				}
			}
		$myActivityLog.=Yii::t('SocialBulkInviteModule.base','-- end Set New Invites --').$myBR.$myBR; 
		return $myActivityLog; 
		}
	
	public function myRemoveCurrentInvites(){
		$myActivityLog=''; $myBR='<br>'; 
		$myActivityLog.=$myBR.Yii::t('SocialBulkInviteModule.base','-- begin Remove Invites --').$myBR; 
		
		$GetMyInvites_cmd=Yii::$app->db->createCommand("
			SELECT user_invite.id as hhID, social_bulk_invite.id as sbiID, social_bulk_invite.invite_email as sbiEmail 
				FROM user_invite,social_bulk_invite 
				WHERE user_invite.email COLLATE utf8mb4_unicode_520_ci = social_bulk_invite.invite_email COLLATE utf8mb4_unicode_520_ci;
			")->queryAll(); 
		
		$DeleteMatchingHHInvites_cmd=Yii::$app->db->createCommand("DELETE FROM user_invite WHERE id=:hhID;");
		$ResetMatchingSBIInvites_cmd=Yii::$app->db->createCommand("UPDATE social_bulk_invite SET invite_queued=0,invite_exists=0,date_updated=NOW() WHERE id=:sbiID;");
		
		foreach($GetMyInvites_cmd as $GetMyInvites){
			$hhID=$GetMyInvites['hhID']; 
			$sbiID=$GetMyInvites['sbiID']; 
			$sbiEmail=$GetMyInvites['sbiEmail']; 
			$DeleteMatchingHHInvites_cmd->bindValue(':hhID',$hhID)->query(); 
			$ResetMatchingSBIInvites_cmd->bindValue(':sbiID',$sbiID)->query(); 
			$myActivityLog.=$sbiEmail.Yii::t('SocialBulkInviteModule.base',': removed from HH Invites and reset here').$myBR; 
			}
		$myActivityLog.=Yii::t('SocialBulkInviteModule.base','-- end Remove Invites --').$myBR.$myBR; 
		return $myActivityLog; 
		}
	
	public function myAlertMessage($title,$body){
		return $this->renderAjax('response', [
					'title' => $title,
					'body' => $body,
					]);
		}
	
	public function MyDataRequest(){
		if(Yii::$app->request->get('SocBulkInviteDL')=='Yes'){
			$MyTabChar="\t"; 
			//id,invite_email,invite_queued,invite_exists,member_id,full_member,date_created,date_updated,times_sent
			$dlsocBulkInviteFile='id'.$MyTabChar.'invite_email'.$MyTabChar.'invite_queued'.$MyTabChar.'invite_exists'.$MyTabChar.'member_id'.$MyTabChar.'full_member'.$MyTabChar.'date_created'.$MyTabChar.'date_updated'.$MyTabChar.'times_sent'."\n";
			
			$dlsocBulkInvite_cmd=Yii::$app->db->createCommand("SELECT id,invite_email,invite_queued,invite_exists,member_id,full_member,date_created,date_updated,times_sent 
				FROM social_bulk_invite ORDER BY id ASC;")->queryAll(); 
			foreach($dlsocBulkInvite_cmd as $dlsocBulkInvite_row){
				$dlsocBulkInviteFile.=$dlsocBulkInvite_row['id'].$MyTabChar.$dlsocBulkInvite_row['invite_email'].$MyTabChar.$dlsocBulkInvite_row['invite_queued'].$MyTabChar.$dlsocBulkInvite_row['invite_exists'].$MyTabChar.$dlsocBulkInvite_row['member_id'].$MyTabChar.$dlsocBulkInvite_row['full_member'].$MyTabChar.$dlsocBulkInvite_row['date_created'].$MyTabChar.$dlsocBulkInvite_row['date_updated'].$MyTabChar.$dlsocBulkInvite_row['times_sent']."\n";
				}
			echo $dlsocBulkInviteFile; 
			exit;
			}
		}
	
	}

?>
