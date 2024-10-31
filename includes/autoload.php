<?php // Version 1.0.2
namespace Pressenter;

spl_autoload_register(function($objName) {
	$objName = str_replace(__NAMESPACE__.'\\', '', $objName);
	$stack = explode('\\', $objName);
	$objName = array_pop($stack);
	$path = implode(DIRECTORY_SEPARATOR, $stack);
	$fileName = __DIR__.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$objName;
	if(file_exists("$fileName.class.php")) require_once "$fileName.class.php";
	elseif(file_exists("$fileName.trait.php")) require_once "$fileName.trait.php";
});