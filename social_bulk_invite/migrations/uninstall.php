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

use humhub\components\Migration;

class uninstall extends Migration{
	public function up(){
		$this->dropTable('social_bulk_invite');
		}
	public function down(){
		echo "uninstall does not support migration down.\n";
		return false;
		}
	}
