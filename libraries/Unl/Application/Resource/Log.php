<?php

/**
 * Override the default behavior of the Zend log resource to return a Unl_Log object instead.
 * @author tsteiner
 */
class Unl_Application_Resource_Log extends Zend_Application_Resource_Log
{
    /**
     * Copied from Zend_Application_Resource_Log and modified to use Unl_Log instead of Zend_Log.
     * @see Zend_Application_Resource_Log::getLog()
     */
    public function getLog()
    {
        if (null === $this->_log) {
            $options = $this->getOptions();
            $log = Unl_Log::factory($options);
            $this->setLog($log);
        }
        return $this->_log;
    }
}
