<?php

namespace Bundles\Jolt;
use Exception;
use e;

/**
 * Jolt Bundle
 * @author Nate Ferrero
 */
class Bundle {

	public function route($path) {
		$file = implode('/', $path);
		$file = __DIR__ . '/library/script/' . $file;
		e\disable_trace();
		header("Content-Type: text/javascript");
		if(file_exists($file))
			readfile($file);
		e\complete();
	}

	/**
	 * Handle Jolt redirects
	 * @author Nate Ferrero
	 */
	public function _on_redirect($url) {
		if(!isset($_POST['@jolt']))
			return;
		header('Content-Type: text/json');
		echo e\json_encode_safe(array('redirect' => $url));
		exit;
	}

}