<?php

namespace humhub\modules\social_bulk_invite;

use Yii;
use yii\helpers\Url;
use humhub\models\Setting;

use humhub\modules\ui\menu\MenuLink;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\admin\permissions\ManageModules;

class Module extends \humhub\components\Module
{
	/**
	 * Enables this module
	 */
	public function enable()
	{
		parent::enable();
		
		$social_bulk_invite=Yii::$app->getModule('social_bulk_invite'); 
		
		if ($social_bulk_invite->settings->get('theSpace') == '') {
			$social_bulk_invite->settings->set('theSpace', 1); 
			}
		//if ($social_bulk_invite->settings->get('theInvitees') == '') {
			$social_bulk_invite->settings->set('theInvitees', ''); 
		//	}
		if ($social_bulk_invite->settings->get('theSaveCount') == '') {
			$social_bulk_invite->settings->set('theSaveCount', 0); 
			}
		if ($social_bulk_invite->settings->get('theSendRate') == '') {
			$social_bulk_invite->settings->set('theSendRate', 4); 
			}
		if ($social_bulk_invite->settings->get('theInviteLang') == '') {
			$social_bulk_invite->settings->set('theInviteLang', 'en'); 
			}
		if ($social_bulk_invite->settings->get('showDebug') == '') {
			$social_bulk_invite->settings->set('showDebug', 0); 
			}
		}
	
	public static function onAdminMenuInit($event){
		
		if (!Yii::$app->user->can(ManageModules::class)) {
			return;
			}
		
		/** @var AdminMenu $menu */
		$menu = $event->sender;
		$menu->addEntry(new MenuLink([
			'label' => 'Social Bulk Invite',
			'url' => Url::to(['/social_bulk_invite/config/config']),
			//'group' => 'manage',
			'icon' => 'envelope-open-o',//address-book-o; reply-all
			'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'social_bulk_invite' && Yii::$app->controller->id == 'admin'),
			'sortOrder' => 700,
			]));
		
		}

}

?>
