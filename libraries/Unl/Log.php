<?php 

/**
 * Extends the Zend_Log class to add support for logging PHP Fatal errors and unhandled exceptions.
 * Also adds support for "registerErrorHandler" option to factory().
 * @author tsteiner
 */
class Unl_Log extends Zend_Log
{
    /**
     * Additionally register exception handler and shutdown methods.
     * @see Zend_Log::registerErrorHandler()
     */
    public function registerErrorHandler()
    {
        parent::registerErrorHandler();
        
        set_exception_handler(array($this, 'exceptionHandler'));
        register_shutdown_function(array($this, 'shutdownHandler'));
        
        return $this;
    }
    
    /**
     * When an unhandle exception occurs, pass it to the Zend_Log errorHandler then throw it again.
     * @param Exception $e
     * @throws Exception
     */
    public function exceptionHandler(Exception $e)
    {
        $this->errorHandler(E_ERROR, $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTrace());
        throw $e;
    }
    
    /**
     * When PHP exits on a fatal error, pass it to the Zend_Log errorHandler.
     */
    public function shutdownHandler()
    {
        $error = error_get_last();
        if ($error['type'] != E_ERROR) {
            return;
        }
        
        $this->errorHandler($error['type'], $error['message'], $error['file'], $error['line'], array());
    }
    
    /**
     * Factory to construct the logger and one or more writers
     * based on the configuration array
     * 
     * @param array|Zend_Config $config
     * @return Unl_Log
     */
    static public function factory($config = array())
    {
        $registerErrorHandler = FALSE;
            
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        }
        
        if (array_key_exists('registerErrorHandler', $config)) {
            $registerErrorHandler = (bool) $config['registerErrorHandler'];
            unset($config['registerErrorHandler']);
        }
        
        $log = self::_zend_factory($config);
        
        if ($registerErrorHandler) {
            $log->registerErrorHandler();
        }
        
        return $log;
    }
    
    /**
     * Copied in whole from Zend_Log::factory because we need "self" to refer to Unl_Log.
     *
     * @param  array|Zend_Config Array or instance of Zend_Config
     * @return Unl_Log
     * @throws Zend_Log_Exception
     */
    static protected function _zend_factory($config = array())
    {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        }
    
        if (!is_array($config) || empty($config)) {
            /** @see Zend_Log_Exception */
            require_once 'Zend/Log/Exception.php';
            throw new Zend_Log_Exception('Configuration must be an array or instance of Zend_Config');
        }
    
        $log = new self;
    
        if (array_key_exists('timestampFormat', $config)) {
            if (null != $config['timestampFormat'] && '' != $config['timestampFormat']) {
                $log->setTimestampFormat($config['timestampFormat']);
            }
            unset($config['timestampFormat']);
        }
    
        if (!is_array(current($config))) {
            $log->addWriter(current($config));
        } else {
            foreach($config as $writer) {
                $log->addWriter($writer);
            }
        }
    
        return $log;
    }
}
