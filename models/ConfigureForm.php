<?php

namespace humhub\modules\social_bulk_invite\models;

use Yii;

class ConfigureForm extends \yii\base\Model
{
	
	public $theSpace;
	public $theInvitees;
	public $theSaveCount;
	public $theSendRate;
	public $theInviteLang;
	public $showDebug;
	
	public function rules()
	{
		return array(
			array('theSpace', 'required'),
			array('theSpace', 'integer', 'min' => 0, 'max' => 5000),
			array('theInvitees', 'safe'),
			array('theSaveCount', 'integer', 'min' => 0, 'max' => 1000),
			array('theSendRate', 'required'),
			array('theSendRate', 'integer', 'min' => 1, 'max' => 5000),
			array('theInviteLang', 'safe'),
			array('showDebug', 'integer', 'min' => 0, 'max' => 2),
		);
	}
	
	
	public function attributeLabels()
	{
		
		$theSpace_title=Yii::t('SocialBulkInviteModule.base','The Space ID Where Guests Will Land'); 
		$theInvitees_title=Yii::t('SocialBulkInviteModule.base','Email addresses to invite -- only valid emails, 1 per line..'); 
		$theSendRate_title=Yii::t('SocialBulkInviteModule.base','The delay between each sent invite, in seconds (1s min.)'); 
		$theInviteLang_title=Yii::t('SocialBulkInviteModule.base','The invite Language'); 
		$showDebug_title=Yii::t('SocialBulkInviteModule.base','Show Debug Info?'); 
		
		return array(
			'theSpace' => $theSpace_title,
			'theInvitees' => $theInvitees_title,
			'theSendRate' => $theSendRate_title,
			'theInviteLang' => $theInviteLang_title,
			'showDebug' => $showDebug_title,
		);
	}

}
