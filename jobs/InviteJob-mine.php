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

namespace humhub\modules\social_bulk_invite\jobs;

//use humhub\modules\user\components\ActiveQueryUser;
use humhub\modules\user\models\Invite;
use humhub\modules\user\services\InviteRegistrationService;
//use humhub\modules\user\models\User;

//use humhub\components\Application; 

use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\helpers\Url;

//use humhub\modules\queue\LongRunningActiveJob;

use Yii;

/*
 * 
 * For this one to work, you have to copy the setLanguage() function from:
 * protected/humhub/components/Application.php	(91)
 * to: 
 * protected/humhub/components/console/Application.php
 * 
 * just add it to the end.. (76)
 * 
 */

class InviteJob extends BaseObject implements JobInterface
{
	public $inviteEmail;
	public $inviteSpace;
	public $inviteOrigin;
	public $inviteLanguage;
	
	
	
	public function execute($queue){
		
		$userInvite = new Invite([
				'email' => $this->inviteEmail,
				'source' => Invite::SOURCE_INVITE,
				'user_originator_id' => $this->inviteOrigin,
				'space_invite_id' => $this->inviteSpace,
				'language' => $this->inviteLanguage,
				]); 
		//$userInvite->email = $this->inviteEmail; 
		//$userInvite->source = 'invite'; 
		//$userInvite->user_invite_queued = $this->inviteOrigin; 
		//$userInvite->user_originator_id = $this->inviteOrigin; 
		//$userInvite->space_invite_id = $this->inviteSpace; // the space ID
		//$userInvite->language = $this->inviteLanguage; 
		//$userInvite->language = 'fr-FR'; //en-US
		if($userInvite->validate() && $userInvite->save()){
		//\humhub\components\Application::setLanguage($this->inviteLanguage); 
			$userInvite->sendInviteMail(); 
			return true; 
			}
		}
		
	public function setLanguage($value)
	{
		if (!empty($value)) {
		    $this->language = $value;
		}
	}
		

	}


