<?php

/**
 * A Zend_Auth_Adapter that will authenticate users based on a whitelist of IP Addresses.
 * The whitelist is an array where each key is an IP addres and each value is a username.
 * @author tsteiner
 *
 */
class Unl_Auth_Adapter_IpWhitelist implements Zend_Auth_Adapter_Interface
{
    /**
     * An array of whitelisted IP addresses.
     * @var array
     */
    protected $_whitelist = array();
    
    /**
     * The client's IP Address.
     * @var string
     */
    protected $_clientIp;

    /**
     * A list of proxies to trust when a X_FORWARDED_FOR header exists.
     * @var array
     */
    protected $_trustedProxyAddresses = array();

    /**
     * @param array $whitelist
     */
    public function __construct($whitelist = array())
    {
        $this->setWhitelist($whitelist);
    }
    
    /**
     * Sets the whitelist to the supplied array.
     * @param array $whitelist
     * @throws Zend_Exception
     */
    public function setWhitelist($whitelist)
    {
        if (!is_array($whitelist)) {
            throw new Zend_Exception('Whitelist is not an array!');
        }
        $this->_whitelist = array();
        foreach ($whitelist as $ipAddress => $username) {
            $this->addToWhitelist($ipAddress, $username);
        }
    }
    
    /**
     * Register an IP address to a user.
     * @param string $ipAddress
     * @param srting $username
     * @throws Zend_Exception
     */
    public function addToWhitelist($ipAddress, $username)
    {
        if (!Zend_Validate::is($ipAddress, 'Ip')) {
            throw new Zend_Exception('The entry "' . $ipAddress . '" is not an IP address!');
        }
        $this->_whitelist[$ipAddress] = $username;
    }
    
    public function getClientIpAddress()
    {
        if (!$this->_clientIp) {
            $request = Zend_Controller_Front::getInstance()->getRequest();
            if ($request instanceof Zend_Controller_Request_Http) {
                $this->_clientIp = $request->getClientIp(false);
            } else if (isset($_SERVER['REMOTE_ADDR'])) {
                $this->_clientIp = $_SERVER['REMOTE_ADDR'];
            } else {
                throw new Zend_Exception('Could not determine client IP address');
            }

            if (in_array($this->_clientIp, $this->getTrustedProxyAddresses())) {
                if ($request instanceof Zend_Controller_Request_Http) {
                    $this->_clientIp = $request->getClientIp(true);
                } else {
                    $this->_clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
                }
            }
        }
        return $this->_clientIp;
    }
    
    public function setClientIpAddress($ipAddress)
    {
        if (!Zend_Validate::is($ipAddress, 'Ip')) {
            throw new Zend_Exception('"' . $ipAddress . '" is not an IP address!');
        }
        $this->_clientIp = $ipAddress;
    }
    
    public function authenticate()
    {
        foreach ($this->_whitelist as $ipAddress => $username) {
            if ($this->getClientIpAddress() == $ipAddress) {
                return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $username, array('Authentication successful.'));
            }
        }
        
        return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, NULL, array('Client IP address not on whitelist.'));
    }

    public function setTrustedProxyAddresses($trustedProxyAddresses)
    {
        if (!is_array($trustedProxyAddresses)) {
            throw new Exception('$trustedProxyAddresses must be an array.');
        }

        $this->_trustedProxyAddresses = $trustedProxyAddresses;
    }

    public function getTrustedProxyAddresses()
    {
        return $this->_trustedProxyAddresses;
    }
}