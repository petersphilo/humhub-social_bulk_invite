<?php

namespace humhub\modules\social_bulk_invite\jobs;

use humhub\modules\user\components\ActiveQueryUser;
use humhub\modules\user\models\Invite;
use humhub\modules\user\models\User;

use humhub\components\Application; 

use yii\base\BaseObject;

use Yii;

use humhub\modules\queue\LongRunningActiveJob;

class InviteJob extends BaseObject implements \yii\queue\JobInterface
{
	public $inviteEmail;
	public $inviteSpace;
	public $inviteOrigin;
	public $inviteLanguage;
	
	
	
	public function execute($queue){
		
		$userInvite = new Invite(); 
		$userInvite->email = $this->inviteEmail; 
		$userInvite->source = 'invite'; 
		//$userInvite->user_invite_queued = $this->inviteOrigin; 
		$userInvite->user_originator_id = $this->inviteOrigin; 
		$userInvite->space_invite_id = $this->inviteSpace; // the space ID
		$userInvite->language = $this->inviteLanguage; 
		//$userInvite->language = 'fr-FR'; //en-US
		if($userInvite->validate() && $userInvite->save()){
			$userInvite->sendInviteMail(); 
			return true; 
			}
		}
		
		

	}

?>
