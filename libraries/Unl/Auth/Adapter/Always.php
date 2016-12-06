<?php

require_once 'Zend/Auth/Adapter/Interface.php';

class Nmc_Auth_Adapter_Always implements Zend_Auth_Adapter_Interface
{
    protected $_userName;
    protected $_succeed;

    public function __construct($userName, $succeed = true)
    {
        $this->_userName = $userName;
        $this->_succeed = $succeed;
    }

    public function authenticate()
    {
        if ($this->_succeed) {
            return new Zend_Auth_Result(true, $this->_userName);
        } else {
            return new Zend_Auth_Result(false, null, array('All authentications disabled.'));
        }
    }
}
