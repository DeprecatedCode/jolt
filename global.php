<?php

function jolt() {
	static $__resources = array();
	
	$args = func_get_args();
	$map = array_shift($args);
	
	if(!is_string($map) || strlen($map) < 1)
		throw new Exception("Invalid resource map");
	
	if(isset($__resources[$map]))
		return $__resources[$map];
	
	$__file = str_replace('.', '/', $map);
	$__file = \jolt\root . "/--resources/$__file.php";
	
	if(!is_file($__file))
		throw new Exception("Resource `$map` file `$__file` does not exist");
	
	require_once($__file);
	
	if(!isset($resource))
		throw new Exception("Resource at `$__file` does not define `\$resource`");
		
	$__resources[$map] = $resource;
	return $__resources[$map];
}