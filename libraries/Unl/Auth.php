<?php

class Unl_Auth extends Zend_Auth
{
    protected $_adapters = array();

    /**
     * The one true instance
     *
     * @var Unl_Auth
     */
    static protected $_instance;

    /**
     * Return the one true instance
     *
     * @return Unl_Auth
     */
    static public function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new Unl_Auth();
        }
        return self::$_instance;
    }

    public function pushAdapter(Zend_Auth_Adapter_Interface $adapter)
    {
        $this->_adapters[] = $adapter;
    }

    /**
     * Authenticates against the supplied adapter
     *
     * @param  Zend_Auth_Adapter_Interface $adapter
     * @return Zend_Auth_Result
     */
    public function authenticate(Zend_Auth_Adapter_interface $adapter = null)
    {
        if ($adapter) {
            return parent::authenticate($adapter);
        }

        if (count($this->_adapters) == 0) {
            throw new Zend_Auth_Exception('No authentication adapters supplied');
        }

        $messages = array();

        foreach ($this->_adapters as $adapter) {
            $result = parent::authenticate($adapter);
            if($result->isValid()) {
                return $result;
            } else {
                foreach ($result->getMessages() as $message) {
                    $messages[] = $message;
                }
            }
        }

        return new Zend_Auth_Result(false, null, $messages);
    }
}