<?php

/**
 * Extend the Zend_Db_Table to add the singleton pattern.
 */
abstract class Unl_Db_Table_Abstract extends Zend_Db_Table_Abstract
{
    /**
     * Storage for the instances of each subclassed table.
     * @var array
     */
    static protected $_instances = array();
    
    /**
     * Returns the singleton instance of the called class.
     * @return Unl_Db_Table_Abstract
     */
    static public function getInstance()
    {
        $class = get_called_class();
        if (!isset(self::$_instances[$class])) {
            self::$_instances[$class] = new $class();
        }
        return self::$_instances[$class];
    }
}
