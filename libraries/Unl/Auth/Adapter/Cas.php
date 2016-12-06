<?php

require_once 'Zend/Auth/Adapter/Interface.php';

class Unl_Auth_Adapter_Cas implements Zend_Auth_Adapter_Interface
{
    /**
     * The Unl_Cas adapter to use to authenticate users.
     * @var Unl_Cas
     */
    protected $_adapter;
    
    /**
     * Constructor
     *
     * @param Unl_Cas $adapter
     */
    public function __construct(Unl_Cas $adapter)
    {
        $this->setAdapter($adapter);
    }
    
    /**
     * Changes the Unl_Cas adapter used by the object.  
     * @param Unl_Cas $adapter
     */
    public function setAdapter(Unl_Cas $adapter)
    {
        $this->_adapter = $adapter;
    }
    
    /**
     * Gets the Unl_Cas adapter used by the object.
     * @return Unl_Cas
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }
    
    /**
     * Authenticates ticket
     *
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        $session = new Zend_Session_Namespace(__CLASS__);
        unset($session->currentUser);
        if ($this->_adapter->getTicket()) {
            if ($this->_adapter->validateTicket()) {
            	$session->currentUser = $this->_adapter->getUsername();
                return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $this->_adapter->getUsername(), array('Authentication successful.'));
            } else {
                return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, NULL, array('Invalid or expired CAS ticket.'));
            }
        } else {
            return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, NULL, array('No CAS Ticktet.'));
        }
    }
    
    /**
     * Returns TRUE if the currently logged in user (if any) was authenticated by this adapter.
     * @return bool
     */
    public static function isCasSession()
    {
    	$session = new Zend_Session_Namespace(__CLASS__);
    	if (isset($session->currentUser) &&
    		Zend_Auth::getInstance()->hasIdentity() &&
    		Zend_Auth::getInstance()->getIdentity() == $session->currentUser) {
    		
    		return TRUE;
    	}
    	return FALSE;
    }
    
}