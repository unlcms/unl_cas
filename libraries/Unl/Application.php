<?php

/**
 * Currently a helper class for accessing the Zend_Application instance.
 * @author tsteiner
 */
class Unl_Application
{
    /**
     * Attempts to return the current application's configuration.
     * If we're not running as a Zend_Application an empty array is returned.
     * 
     * @return array
     */
    static public function getOptions()
    {
        $application = self::getInstance();
        if (!$application) {
            return array();
        }
        
        return $application->getOptions();
    }
    
    /**
     * Attempts to return the current Zend_Application instance.
     * If we're not running as a Zend_Application, NULL is returned.
     * 
     * @return Zend_Application
     */
    static public function getInstance()
    {
        if (!class_exists('Zend_Controller_Front')) {
            return NULL;
        }
        
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        if (!$bootstrap instanceof Zend_Application_Bootstrap_BootstrapAbstract) {
            return NULL;
        }
        
        $application = $bootstrap->getApplication();
        
        if (!$application instanceof Zend_Application) {
            return NULL;
        }
        
        return $application;
    }
}
