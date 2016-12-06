<?php

require_once ('Zend/Loader.php');

class Unl_Loader extends Zend_Loader
{
	public static function loadClass($class, $dirs = null)
	{
		return parent::loadClass($class, $dirs);
	}
	
	public static function autoload($class)
	{
		if (substr($class, -5) != 'Model' || substr($class, 0, 9) == 'Unl_Model') {
			return parent::autoload($class);
		}
	
		$parts = explode('_', $class);
		if (count($parts) == 1) {
			$module = 'default';
			$model = $parts[0];
		} else if (count($parts) == 2) {
			$module = $parts[0];
			$model = $parts[1];
		} else {
			return parent::autoload($class);
		}
		
		$path = MODULES_DIR . DIRECTORY_SEPARATOR
		      . strtolower($module) . DIRECTORY_SEPARATOR
		      . 'models' . DIRECTORY_SEPARATOR
		      . $model . '.php';
		if (!self::isReadable($path)) {
			return parent::autoload($class);
		}
		
		self::loadFile($path);
		
		return $class;
	}
}
