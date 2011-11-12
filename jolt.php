<?php

/**
 * Jolt for PHP by Nate Ferrero
 * http://nateferrero.com/jolt
 */
namespace jolt;
use Exception;
use ErrorException;
use stdClass as Object;

/**
 * Save script name
 */
define('jolt\\script', __FILE__);

/**
 * Load feature
 */
function feature($feature, $params = array()) {
	extract($params);
	require_once(__DIR__ . "/features/$feature.php");
}

/**
 * Includes
 */
require_once('global.php');

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
	define('jolt\\in_ex', true);
	feature('exception', array('exception' => $e));
}

set_exception_handler('jolt\\ex');

/**
 * Error handler
 */
function err($n, $s, $f, $l) {
	if(!defined('jolt\\in_ex'))
		throw new ErrorException($s, 0, $n, $f, $l);
	
	/**
	 * We are already displaying an exception
	 */
	echo "<style>body {font-family: sans-serif;}</style>
		<h1>Jolt</h1><h2>Error during exception display</h2>
		<p><strong>Error:</strong> $s</p>
		<p><strong>File:</strong> $f</p>
		<p><strong>Line:</strong> $l</p>";
	exit;
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
function render($file) {
	
	/**
	 * Specify system defaults
	 */
	$jolt = new Object;
	$jolt->template = 'html';
	$jolt->template_group = 'default';
	$jolt->url = url;
	$jolt->root = root;
	$jolt->file = $file;
	unset($file);
	
	/**
	 * Load user defaults
	 */
	if(is_file('--defaults.php'))
		require('--defaults.php');
	
	$jolt->stack = array($jolt->file);
	while(count($jolt->stack) > 0) {
		$jolt->file = array_shift($jolt->stack);
		if(!is_file($jolt->file))
			throw new Exception("Required template `$jolt->file` not found while rendering `$jolt->url`");
			
		/**
		 * Capture template output
		 */
		ob_start();
		require $jolt->file;
		$jolt->buffer = ob_get_clean();
		
		/**
		 * Loop through defined vars
		 */
		foreach(array_keys(get_defined_vars()) as $_____variable_____) {
			$_____variable_____ = strtolower($_____variable_____);
			if(strpos($jolt->buffer, '{'.$_____variable_____) === false)
				continue;
			$jolt->regex = "/\{$_____variable_____([a-z0-9_.,\\s$-]*)\}/";
			preg_match_all($jolt->regex, $jolt->buffer, $jolt->matches);
			foreach(array_unique($jolt->matches[1]) as $jolt->map) {
				/**
				 * Reset for each use
				 */
				$jolt->insert = null;
			
				/**
				 * Use the variable
				 */
				$jolt->value = $$_____variable_____;
				if($jolt->map === '')
					$jolt->insert =& $jolt->value;
				
				/**
				 * Dive into the variable
				 */
				else if(substr($jolt->map, 0, 1) === '.') {
					$jolt->insert = &$jolt->value;
					/**
					 * Null values will not be inserted
					 */
					if(is_null($jolt->insert))
						break;
				
					/**
					 * Get segments of accessor map
					 */
					$jolt->segments = explode('.', $jolt->map);
					array_shift($jolt->segments);
					foreach($jolt->segments as $jolt->segment) {
					
						/**
						 * Handle dynamic segments
						 */
						if(substr($jolt->segment, 0, 1) === '$') {
							$jolt->segment = substr($jolt->segment, 1);
							$jolt->segment = $$jolt->segment;
						}
					
						/**
						 * Handle String access
						 */
						if(is_string($jolt->insert)) {
							$jolt->subs = explode(',', $jolt->segment);
							$jolt->temp = '';
							foreach($jolt->subs as $jolt->sub) {
								$jolt->sub = explode('-', $jolt->sub);
								if(count($jolt->sub) == 1)
									$jolt->sub[] = $jolt->sub[0];
								$jolt->temp .= substr($jolt->insert, $jolt->sub[0], $jolt->sub[1] - $jolt->sub[0] + 1);
							}
							$jolt->insert = $jolt->temp;
						}
					
						/**
						 * Handle Array access
						 */
						else if(is_array($jolt->insert)) {
							if(isset($jolt->insert[$jolt->segment]))
								$jolt->insert = $jolt->insert[$jolt->segment];
							else
								$jolt->insert = null;
						}
					
						/**
						 * Handle Object access
						 */
						else if(is_object($jolt->insert)) {
							if(isset($jolt->insert->{$jolt->segment}))
								$jolt->insert = $jolt->insert->{$jolt->segment};
							else
								$jolt->insert = null;
						}
					}
				}
				
				/**
				 * Null values are blank
				 */
				if(is_null($jolt->insert))
					$jolt->insert = '';
				
				/**
				 * Convert objects and arrays to string
				 */
				if(!is_string($jolt->insert)) {
					render_var($jolt->insert);
				}
			
				/**
				 * Replace all instances of this accessor
				 */
				$jolt->replace = '{'.$_____variable_____.$jolt->map.'}';
				$jolt->buffer = str_replace($jolt->replace, $jolt->insert, $jolt->buffer);
			}
		}
		
		/**
		 * Clear the only newly-created variable
		 */
		unset($_____variable_____);
		
		/**
		 * Set the current buffer contents as the contents of the parent template and continue
		 */
		$jolt->content = $jolt->buffer;
		unset($jolt->buffer);
		
		/**
		 * Cascade up the template chain if one is set, remember all sub modules are already rendered at this point
		 * Templates can include additional modules easily by specifying the module in the PHP code
		 */
		if(isset($jolt->template)) {
			if(!isset($jolt->template_group))
				throw new Exception("Template set to `$jolt->template`, but `\$jolt->template_group` not defined");
			$jolt->stack[] = root . "/--templates/$jolt->template_group/$jolt->template.php";
			unset($jolt->template);
		}

	}
		
	return $jolt->content;
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
 * Load jolt domains configuration
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
	 * Check valid base dir
	 */
	if(!is_dir($base))
		throw new Exception("Directory `$base` for host `$host` does not exist");
	
	/**
	 * Define root
	 */
	define('jolt\\root', $base);
	
	/**
	 * Change working dir
	 */
	chdir(root);
	
	/**
	 * Route the request
	 */
	$url = $_SERVER['REDIRECT_URL'];
	if(substr($url, -1) === '/')
		$url .= 'index';
		
	/**
	 * Define the url constant
	 */
	define('jolt\\url', $url);
	
	/**
	 * Look for url.* if url doesn't contain a .
	 */
	$search = $base . $url . (strpos($url, '.') !== false ? '' : '.*');
	$matches = glob($search);
	if(count($matches) === 0) {
		/**
		 * Check for a global not-found page
		 * TODO add realm not-found, like forum/not-found.php
		 */
		$matches = glob($base . '/not-found.*');
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
	 * Handle PHP Files
	 */
	if(isset($exts['php'])) {
		try {
			echo render($exts['php']);
		} catch(Exception $e) {
			throw new Exception("There was a problem rendering `".$exts['php']."`", 0, $e);
		}
	}
	
	/**
	 * Handle HTML Files
	 */
	else if(isset($exts['html'])) {
		try {
			echo render($exts['html']);
		} catch(Exception $e) {
			throw new Exception("There was a problem rendering `".$exts['html']."`", 0, $e);
		}
	}
	
	/**
	 * Handle plain text Files
	 */
	else if(isset($exts['txt'])) {
		header('Content-Type: text/plain');
		header('Content-Length: ' . filesize($exts['txt']));
		readfile($exts['txt']);
	}
	
	/**
	 * No valid type found
	 */
	else {
		$debug = implode(', ', $exts);
		throw new Exception("Jolt cannot render the path `$url` because no file of valid type exists, found `$debug`");
	}
	
	/**
	 * Exit PHP
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