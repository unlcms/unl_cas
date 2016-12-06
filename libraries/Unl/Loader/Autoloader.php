<?php

require_once ('Zend/Loader/Autoloader/Interface.php');

class Unl_Loader_Autoloader implements Zend_Loader_Autoloader_Interface
{
    public function autoload($class)
    {
        if (substr($class, -5) != 'Model' || substr($class, 0, 9) == 'Unl_Model') {
            return false;
        }
    
        $parts = explode('_', $class);
        if (count($parts) == 1) {
            $module = 'default';
            $model = $parts[0];
        } else if (count($parts) == 2) {
            $module = $parts[0];
            $model = $parts[1];
        } else {
            return false;
        }
        
        $path = MODULES_DIR . DIRECTORY_SEPARATOR
              . strtolower($module) . DIRECTORY_SEPARATOR
              . 'models' . DIRECTORY_SEPARATOR
              . $model . '.php';
        if (!Zend_Loader::isReadable($path)) {
            return false;
        }
        
        require_once $path;
        
        return $class;
    }
}
