<?php

/**
 * A Zend Controller Plugin that facilitates using transparent CAS authentication.
 * 
 * To enable this module, add the following lines to your application.ini:
 * resources.frontController.plugins[] = Unl_Controller_Plugin_Auth_Cas
 * unl.cas.controller = <name of the controller that extends Unl_Controller_Action_Authenticate>
 * 
 * @author tsteiner
 *
 */
class Unl_Controller_Plugin_Auth_Cas extends Zend_Controller_Plugin_Abstract
{
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
    	// If the current user wasn't authenticated by CAS, don't try to validate their session.
    	if (Zend_Auth::getInstance()->hasIdentity() && !Unl_Auth_Adapter_Cas::isCasSession()) {
    		return;
    	}
    	
        $front = Zend_Controller_Front::getInstance();
        
        $options = $front->getParam('bootstrap')->getOptions();
        $casOptions = (isset($options['unl']['cas']) ? $options['unl']['cas'] : array());
        
        // Get the controller name.  This is required.
        if (isset($casOptions['controller'])) {
            $casController = $casOptions['controller'];
        } else {
            return;
        }
    
        // Get the module name.  This is only required if using modules.
        if (isset($casOptions['module'])) {
            $casModule = $casOptions['module'];
        } else if (!isset($options['resources']['modules'])) {
            $casModule = 'default';
        } else {
            return;
        }
        
        // Get the action name.  This isn't normally needed.
        if (isset($casOptions['action'])) {
            $casAction = $casOptions['action'];
        } else {
            $casAction = 'cas';
        }
        
        // Transparent checks should not be done if the original request is an authentication request.
        if ($request->getModuleName() == $casModule && $request->getControllerName() == $casController) {
            return;
        }
        
        // Transparent checks should not be done on non-HTTP, non-GET requests.
        if (!$request instanceof Zend_Controller_Request_Http || !$request->isGet()) {
            return;
        }
        
        // If there's no SSO cookie, there's no need to do a transparent login unless a user is already logged in.
        if (!array_key_exists('unl_sso', $_COOKIE) && !Zend_Auth::getInstance()->hasIdentity()) {
            return;
        }
        
        // Build the cas service URL.
        $casPath = Zend_Controller_Front::getInstance()->getRouter()->assemble(array(
            'module' => $casModule,
            'controller' => $casController,
            'action' => $casAction,
        ));

        if (parse_url($path, PHP_URL_SCHEME)) {
            $serviceUrl = $casPath;
        } else {
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
                $serviceUrl = 'https://';
            } else {
                $serviceUrl = 'http://';
            }
            $serviceUrl .= $_SERVER['SERVER_NAME'] . $casPath;
        }
        
        // Init the CAS Adapter.
        $casAdapter = new Unl_Cas($serviceUrl, 'https://shib.unl.edu/idp/profile/cas');
        
        // If either the user has no ticket, the ticket is expired, or a user isn't logged in, go ahead with transparent login.
        if ($casAdapter->isTicketExpired() || !Zend_Auth::getInstance()->hasIdentity()) {
            $currentPath = Zend_Controller_Front::getInstance()->getRouter()->assemble(array());
            $currentPath = substr($currentPath, strlen($front->getBaseUrl()));
            
            $session = new Zend_Session_Namespace('Unl_Controller_Action_Authenticate');
            $session->referer = $currentPath;
            
            $casAdapter->setGateway();
            header('Location: ' . $casAdapter->getLoginUrl());
            exit;
        }
    }
}
