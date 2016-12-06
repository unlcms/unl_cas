<?php

require_once 'Zend/Auth/Adapter/Interface.php';

class Unl_Auth_Adapter_Ldap implements Zend_Auth_Adapter_Interface
{

    protected $_connection;
    protected $_userName;
    protected $_password;

    public function __construct(Unl_Ldap $connection, $userName, $password)
    {
        $this->_connection = $connection;
        $this->_userName = $userName;
        $this->_password = $password;
    }

    /**
     * Performs an authentication attempt
     *
     * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        $userName = $this->_userName;
        $password = $this->_password;

        if (!$userName) {
            return new Zend_Auth_Result(false, null, array('LDAP: No username specified.'));
        }

        try {
            $this->_connection->bind("uid=$userName,ou=people,dc=unl,dc=edu", $password);
            $filter = 'uid=' . $userName;
            $this->_connection->search('ou=people,dc=unl,dc=edu', $filter );
        } catch (Unl_Ldap_Exception $e) {
            if ($e->getCode() == 49) {
                return new Zend_Auth_Result(false, null, array($e->getMessage()));
            } else {
                throw new Zend_Auth_Adapter_Exception($e->getMessage(), $e->getCode());
            }
        } catch (Exception $e) {
            throw new Zend_Auth_Adapter_Exception($e->getMessage(), $e->getCode());
        }

        return new Zend_Auth_Result(true, $userName);
    }
}
