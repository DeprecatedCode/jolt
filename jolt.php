<?php

/**
 * Jolt for PHP by Nate Ferrero
 * http://nateferrero.com/jolt
 */
namespace jolt;
use Exception;

/**
 * Redirect
 */
function redirect($url) {
	header('Location: ' . $url);
	exit;
}

/**
 * Exception handler
 */
function ex($e) {
	
	$type = get_class($e);
	$body = $e->getMessage();
	$body = "<p>$body</p>";
	$a = ucfirst(english_article($type));
	$main = "$a $type has occurred";
	$_line = $e->getLine();
	$_file = $e->getFile();
	
	if(strtolower($_file) === strtolower(__FILE__))
		$location = '';
	else
		$location = "<h3><span class='label'>File</span> `$_file` <span class='label'>Line</span> $_line</h3>";
	
	if(defined('jolt\\root')) {
		$root = root;
		$hostinfo = "<h3><span class='label'>Host</span> `$_SERVER[HTTP_HOST]`</h3><h3><span class='label'>Document Root</span> `$root`</h3>";
	}
	else
		$hostinfo = "<h3>No matching host</h3>";
	
	while($e = $e->getPrevious()) {
		if(is_null($e))
			break;
		$type = get_class($e);
		$a = english_article($type);
		$sub = "Which started out as $a $type";
		$line = $e->getLine();
		$file = $e->getFile();
		$location = "<h3><span class='label'>File</span> `$file` <span class='label'>Line</span> $line</h3>";
		$body .= '<div class="prev">'."<h2>$sub</h2><p>$location" . $e->getMessage() . '</p></div>';
	}
	$body = preg_replace('/\\s\\[\\<a.*>]/', '', $hostinfo.$location.$body);
	$body = preg_replace('/`(.*?)`/', '<code>$1</code>', $body);
	
	echo <<<_
<!doctype html>
<html>
	<head>
		<title>Jolt &bull; $type</title>
		<style>
			body {
				cursor: default; font: 14px sans-serif; background: #100; 
				color: #fff; padding: 30px;
			}
			a {color: #f88;}
			h1 {font-size: 22px; margin-top: 0;}
			h2 {font-size: 18px; margin-top: 0; color: #c00;}
			h3 {font-size: 14px; margin-top: 0; color: #dc0; font-weight: normal;}
			.prev {margin: 18px 0; background: #200; padding: 30px;}
			.label {
				border-radius: 4px; background: #dc0; color: #100; font-size: 80%;
				font-weight: bold; margin-right: 4px; padding: 3px 7px 2px;
				box-shadow: inset 0 10px 20px -10px #fec, inset 0 -10px 20px -10px #ca0;
			}
			p, h2 {margin-top: 24px;}
		</style>
	</head>
	<body>
		<h1>Jolt &bull; System Notice</h1>
		<h2>$main</h2>
		$body
	</body>
</html>
_;
}

set_exception_handler('jolt\\ex');

/**
 * Error handler
 */
function err($n, $s, $f, $l) {
	throw new \ErrorException($s, 0, $n, $f, $l);
}

set_error_handler('jolt\\err');

/**
 * Simple lanugage helpers
 */
function english_article($word) {
	switch(strtolower(substr($word, 0, 1))) {
		case 'a': case 'e': case 'i': case 'o': case 'u':
			return 'an';
		case 'h':
			switch(strtolower($word)) {
				case 'hour':
					return 'an';
			}
	}
	return 'a';
}

/**
 * Render a jolt page
 */
function render($__file) {
	$template = 'default';
	if(is_file('--defaults.php'))
		require('--defaults.php');
	$__stack = array($__file);
	while(count($__stack) > 0) {
		$__file = array_shift($__stack);
		ob_start();
		require $__file;
		$__buffer = ob_get_clean();
		foreach(array_keys(get_defined_vars()) as $__variable) {
			$__variable = strtolower($__variable);
			if(strpos($__buffer, '{'.$__variable) === false)
				continue;
			$__regex = "/\{$__variable([a-z0-9_.,\\s$-]*)\}/";
			preg_match_all($__regex, $__buffer, $__matches);
			foreach(array_unique($__matches[1]) as $__map) {
				/**
				 * Reset for each use
				 */
				$__insert = null;
			
				/**
				 * Use the variable
				 */
				$__value = $$__variable;
				if($__map === '')
					$__insert =& $__value;
				
				/**
				 * Dive into the variable
				 */
				else if(substr($__map, 0, 1) === '.') {
					$__insert = &$__value;
					/**
					 * Null values will not be inserted
					 */
					if(is_null($__insert))
						break;
				
					/**
					 * Get segments of accessor map
					 */
					$__segments = explode('.', $__map);
					array_shift($__segments);
					foreach($__segments as $__segment) {
					
						/**
						 * Handle dynamic segments
						 */
						if(substr($__segment, 0, 1) === '$') {
							$__segment = substr($__segment, 1);
							$__segment = $$__segment;
						}
					
						/**
						 * Handle String access
						 */
						if(is_string($__insert)) {
							$__subs = explode(',', $__segment);
							$__temp = '';
							foreach($__subs as $__sub) {
								$__sub = explode('-', $__sub);
								if(count($__sub) == 1)
									$__sub[] = $__sub[0];
								$__temp .= substr($__insert, $__sub[0], $__sub[1] - $__sub[0] + 1);
							}
							$__insert = $__temp;
						}
					
						/**
						 * Handle Array access
						 */
						else if(is_array($__insert)) {
							if(isset($__insert[$__segment]))
								$__insert = $__insert[$__segment];
							else
								$__insert = null;
						}
					
						/**
						 * Handle Object access
						 */
						else if(is_object($__insert)) {
							if(isset($__insert->$__segment))
								$__insert = $__insert->$__segment;
							else
								$__insert = null;
						}
					}
				}
				
				/**
				 * Null values will not be inserted
				 */
				if(is_null($__insert))
					continue;
				
				/**
				 * Convert objects and arrays to string
				 */
				if(!is_string($__insert)) {
					render_var($__insert);
				}
			
				/**
				 * Replace all instances of this accessor
				 */
				$__replace = '{'.$__variable.$__map.'}';
				$__buffer = str_replace($__replace, $__insert, $__buffer);
			}
		}
		
		/**
		 * Set the current buffer contents as the contents of the parent template and continue
		 */
		$__content = $__buffer;
		unset($__buffer);
		
		/**
		 * Cascade up the template chain if one is set, remember all sub modules are already rendered at this point
		 * Templates can include additional modules easily by specifying the module in the PHP code
		 */
		if(isset($template)) {
			if(!isset($template_group))
				throw new Exception("Template set to `$template`, but `template_group` not defined");
			$__stack[] = __DIR__ . "/--templates/$template_group/$template.php";
			unset($template);
		}

	}
		
	return $__content;
}

function render_var(&$var) {
	if($var instanceof \Traversable || is_array($var)) {
		$out = '';
		foreach($var as $item) {
			render_var($item);
			$out .= $item;
		}
		$var = $out;
	}
	
	else if(is_object($var)) {
		if(method_exists($var, 'render')) {
			$var = $var->render();
			render_var($var);
		}
		else
			throw new Exception(get_class($var) . " does not have a `render` method");
	}
}

/**
 * System class
 * Handles domains, basic configuration
 */
class system {
	public static $domains = array();
}

/**
 * Check for proper access
 */
if(!isset($_SERVER['REDIRECT_URL']))
	throw new Exception('You need to enable `mod_rewrite` in Apache and ensure you are using PHP 5.3 or later');

/**
 * Using the jolt system
 */
$file = __DIR__ . '/domains.php';
if(is_file($file) && filesize($file) > 0) {
	
	/**
	 * Load matching domains
	 */	
	require_once($file);
	
	/**
	 * Look for host in domains list
	 */
	$host = $_SERVER['HTTP_HOST'];
	
	if(!isset(system::$domains[$host]))
		throw new Exception("Host `$host` not defined in file `$file`");
	
	/**
	 * Use the directory
	 */
	$base = realpath(system::$domains[$_SERVER['HTTP_HOST']]);
	
	/**
	 * Define root
	 */
	define('jolt\\root', $base);
	
	/**
	 * Check valid base dir
	 */
	if(!is_dir($base))
		throw new Exception("Directory `$base` for host `$host` does not exist");
	
	$url = $_SERVER['REDIRECT_URL'];
	if(strpos($url, $base) === 0)
		$url = substr($url, strlen($base));
	if(substr($url, -1) === '/')
		$url .= 'index';
	$matches = glob(__DIR__ . $url . '.*');
	if(count($matches) === 0) {
		$matches = glob(__DIR__ . 'not-found.*');
		if(count($matches) === 0)
			throw new Exception("No resource found for `$url`");
	}
	
	/**
	 * Loop through all matched extensions
	 */
	$exts = array();
	foreach($matches as $match) {
		$exts[strtolower(pathinfo($match, PATHINFO_EXTENSION))] = $match;
	}
	
	/**
	 * Handle matched resources
	 */
	if(isset($exts['php'])) {
		try {
			echo render($exts['php']);
		} catch(Exception $e) {
			throw new Exception("There's a problem with `".$exts['php']."`", 0, $e);
		}
	}

	/**
	 * Stop PHP processing
	 */
	exit;
}

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
	throw new Exception("Could not write `$file` &mdash; Run this: `sudo touch $file; sudo chmod 777 $file`");
}

/**
 * If everything is set, just redirect to the root URL
 */
redirect(dirname($_SERVER['REQUEST_URI']));