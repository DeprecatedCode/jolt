<?php

namespace jolt;
use Exception;

/**
 * Cache feature
 */
class cache {
	
	public static $dir;
	
	public static function prepare() {
		/**
		 * Make sure the cache directory is enabled
		 */
		self::$dir = $dir = dir . '/cache';
		if(is_dir($dir)) return;
		//mkdir($dir);
		if(!is_writable($dir))
			throw new Exception("Cache directory is not writable: execute command `cache=\"$dir\"; sudo mkdir \$cache; sudo chmod -Rf 777 \$cache;`");
	}
	
}

cache::prepare();