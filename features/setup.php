<?php

namespace jolt;
use Exception;

/**
 * Setup Jolt
 */

$setup = <<<_
<?php

/**
 * Jolt Domains
 */

namespace jolt;

/**
 * Jolt system
 */
system::\$domains['jolt.dev'] = './';

/**
 * Add your custom domains here
 */
system::\$domains['test.dev'] = '../test';
_;
try {
	file_put_contents($file, $setup);
} catch(Exception $e) {
	throw new Exception("Could not write domain configuration file: execute command `sudo touch $file; sudo chmod 777 $file`");
}

/**
 * If everything is set, just redirect to the root URL
 */
redirect(dirname(url));