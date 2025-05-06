<?php

/**
 * Peter Zieseniss (with immense help from Tony GM!)
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

use humhub\modules\user\models\Invite;
//use humhub\modules\user\Module;
use humhub\modules\user\services\InviteRegistrationService;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\helpers\Url;

use yii\db; 
use yii\db\Query; 
use yii\db\Command; 

use humhub\modules\user\models\User;

class ResendInviteJob extends BaseObject implements JobInterface
{
	public $inviteID;
	public $inviteEmail;
	public $inviteSpace;
	public $inviteOriginID;
	public $inviteOriginDisplayName; //displayName
	public $inviteLanguage;
	public $inviteTimesSent;
	public $inviteToken;

	public function execute($queue)
	{
		try {
			
			$userInvite = Invite::findOne(['id' => $this->inviteID]);

			if (!$userInvite->validate()) {
				Yii::error([
					'message' => 'ResendInvite validation failed',
					'email' => $this->inviteEmail,
					'errors' => $userInvite->getErrors(),
				], 'social_bulk_invite');
				return false;
			}
			/*
			if (!$userInvite->save()) {
				Yii::error([
					'message' => 'Invite save failed',
					'email' => $this->inviteEmail,
					'errors' => $userInvite->getErrors(),
				], 'social_bulk_invite');
				return false;
			}

			Yii::info("Invite created successfully for: {$this->inviteEmail}", 'social_bulk_invite');

			// Use InviteRegistrationService to validate token-based invite
			$inviteService = new InviteRegistrationService($userInvite->token);

			if (!$inviteService->isValid()) {
				Yii::error([
					'message' => 'Invite token is invalid or invite not found',
					'email' => $this->inviteEmail,
				], 'social_bulk_invite');
				return false;
			}
			*/
			// Language for mail content
			Yii::$app->language = $this->inviteLanguage ?: Yii::$app->settings->get('defaultLanguage');
			
			$registrationUrl = Url::to(['/user/registration', 'token' => $userInvite->getAttribute('token')], true);

			$mail = Yii::$app->mailer->compose([
				'html' => '@humhub/modules/user/views/mails/UserInvite',
				'text' => '@humhub/modules/user/views/mails/plaintext/UserInvite',
			], [
				'originator' => $userInvite->originator,
				'originatorName' => $userInvite->originator->displayName ?? 'Someone',
				'space' => $userInvite->space,
				'registrationUrl' => $registrationUrl,
			]);

			$mail->setTo($userInvite->email);
			$mail->setSubject(Yii::t('UserModule.invite', 'You\'ve been invited to join %appName%', [
				'%appName%' => Yii::$app->name,
			]));

			$result = $mail->send();

			if ($result) {
				Yii::info("Invite mail re-sent successfully to: {$userInvite->email}", 'social_bulk_invite');
				$userInvite->updateAttributes(['updated_at' => date('Y-m-d H:i:s')]);
				$inviteTimesSent=$this->inviteTimesSent+1; 
				$inviteEmail=$this->inviteEmail; 
				Yii::$app->db->createCommand("UPDATE social_bulk_invite SET times_sent=$inviteTimesSent,date_updated=NOW() WHERE invite_email='$inviteEmail';")->query(); 
			} else {
				Yii::error("Failed to re-send invite mail to: {$userInvite->email}", 'social_bulk_invite');
			}

			return $result;
		} catch (\Throwable $e) {
			Yii::error([
				'message' => 'Exception in InviteJob execution',
				'email' => $this->inviteEmail,
				'exception' => $e->__toString(),
			], 'social_bulk_invite');
			return false;
		}
	}
}