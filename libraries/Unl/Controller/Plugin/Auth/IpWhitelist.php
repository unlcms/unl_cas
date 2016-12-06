<?php

/**
 * A Zend Controller Plugin that facilitates using transparent IP Whitelist authentication.
 * 
 * To enable this module, add the following line to your application.ini:
 * resources.frontController.plugins[] = Unl_Controller_Plugin_Auth_IpWhitelist
 * 
 * and the optional configuration lines:
 * unl.ipWhitelist.table = <name of the database table that contains the whitelist>
 * unl.ipWhitelist.column.ipAddress = <name of the column that contains the IP address>
 * unl.ipWhitelist.column.username = <name of the column that contains the username>
 *
 * @author tsteiner
 *
 */
class Unl_Controller_Plugin_Auth_IpWhitelist extends Zend_Controller_Plugin_Abstract
{
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        // If a user is already logged in, don't try to re-auth.
        if (Zend_Auth::getInstance()->hasIdentity()) {
            return;
        }
        
        // Get the database adapter and options from the bootstrap.
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $db = $bootstrap->getResource('db');
        $options = $bootstrap->getOptions();
        $options = isset($options['unl']['ipWhitelist']) ? $options['unl']['ipWhitelist'] : array();
        $table           = isset($options['table']) ? $options['table'] : 'ip_whitelist';
        $ipAddressColumn = isset($options['columns']['ipAddress']) ? $options['columns']['ipAddress'] : 'ip_address';
        $usernameColumn  = isset($options['columns']['username']) ? $options['columns']['username'] : 'username';
        
        // Not configured.  Don't do anything.
        if (!$db instanceof Zend_Db_Adapter_Abstract) {
            throw new Zend_Exception('A database resource must be defined to use the IP Whitelist transparent authentication.');
        }
        
        // Query the database for the whitelist.
        $select = $db->select();
        $select->from($table, array($ipAddressColumn, $usernameColumn));
        $whitelistData = $db->fetchAll($select);
        
        // Initialize the whitelist auth adapter
        $whitelistAdapter = new Unl_Auth_Adapter_IpWhitelist();
        foreach ($whitelistData as $row) {
            $whitelistAdapter->addToWhitelist($row[$ipAddressColumn], $row[$usernameColumn]);
        }
        
        // Attempt authentication.
        Zend_Auth::getInstance()->authenticate($whitelistAdapter);
    }
}
