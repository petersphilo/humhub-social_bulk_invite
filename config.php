<?php

use humhub\modules\dashboard\widgets\Sidebar;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\components\ModuleManager;

return [
	'id' => 'social_bulk_invite',
	'class' => 'humhub\modules\social_bulk_invite\Module',
	'namespace' => 'humhub\modules\social_bulk_invite',
	'events' => [
		/* ['class' => Sidebar::className(), 'event' => Sidebar::EVENT_INIT, 'callback' => ['humhub\modules\social_bulk_invite\Module', 'onSidebarInit']], */
		['class' => AdminMenu::className(), 'event' => AdminMenu::EVENT_INIT, 'callback' => ['humhub\modules\social_bulk_invite\Module', 'onAdminMenuInit']],
	],
];
?>
