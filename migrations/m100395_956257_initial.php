<?php

use humhub\components\Migration;

class m100395_956257_initial extends Migration{
	
	public function up(){
		$this->createTable('social_bulk_invite', [
			'id' => 'pk',
			'invite_email' => 'varchar(255) NULL',
			'invite_queued' => 'int(11) NULL DEFAULT 0',
			'invite_exists' => 'int(11) NULL DEFAULT 0',
			'member_id' => 'int(11) NULL DEFAULT 0',
			'full_member' => 'int(11) NULL DEFAULT 0',
			'date_created' => 'datetime NULL DEFAULT CURRENT_TIMESTAMP',
			'date_updated' => 'datetime NULL DEFAULT CURRENT_TIMESTAMP',
			'times_sent' => 'int(11) NULL DEFAULT 0',
			], '');
		$this->safeCreateIndex('invite_email','social_bulk_invite','invite_email',false);
		}

	public function down(){
		echo "my_initial_social_bulk_invite does not support migration down.\n";
		return false;
		}
}

//id,invite_email,invite_queued,invite_exists,member_id,full_member,date_created,date_updated,times_sent
