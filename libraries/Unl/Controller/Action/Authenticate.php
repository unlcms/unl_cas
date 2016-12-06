<?php

/**
 * An abstract Zend Controller Action that can be extended to implement UNL CAS logins.
 * Override the _setupUser() and _getDefaultLandingPath() methods to customize.
 */
abstract class Unl_Controller_Action_Authenticate extends Unl_Controller_Action
{
    /**
     * Handles the login action.
     * Users should be sent here to initiate the login process.
     */
    public function loginAction()
    {
        $session = new Zend_Session_Namespace(__CLASS__);
        $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        try {
            $referer = Zend_Uri_Http::factory($_SERVER['HTTP_REFERER']);
        } catch (Exception $e) {
        }
        if ($this->_getParam('referer')) {
            $session->referer = $this->_getParam('referer');
        } else if ($referer && 
            $referer->getHost() == $_SERVER['HTTP_HOST'] &&
            (!$referer->getPort() || $referer->getPort() == $_SERVER['SERVER_PORT']) &&
            substr($referer->getPath(), 0, strlen($baseUrl)) == $baseUrl) {
            $session->referer = substr($referer->getPath(), strlen($baseUrl));
            $session->referer = ltrim($session->referer, '/'); 
            if ($referer->getQuery()) {
                $session->referer .= '?' . $referer->getQuery();
            }
            if ($referer->getFragment()) {
                $session->referer .= '#' . $referer->getFragment();
            }
        } else {
            $session->referer = $this->_getDefaultLandingPath();
        }
        
        $this->_redirect($this->_getCasAdapter()->getLoginUrl());
    }
    
    /**
     * Handles the logout action.
     * Users should be sent here to initiate the logout process.
     */
    public function logoutAction()
    {
        $this->_destroyUser(Zend_Auth::getInstance()->getIdentity());
        Zend_Auth::getInstance()->clearIdentity();
        
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $returnUrl = 'https://';
        } else {
            $returnUrl = 'http://';
        }
        
        $returnUrl .= $_SERVER['SERVER_NAME'] . Zend_Controller_Front::getInstance()->getBaseUrl();
        
        $logoutUrl = $this->_getCasAdapter()->getLogoutUrl($returnUrl);
        $this->_redirect($logoutUrl);
    }
    
    /**
     * Handles the cas action.
     * Users should only arrive here after being redirected from the CAS server.
     */
    public function casAction()
    {
        $auth = Zend_Auth::getInstance();
        $casAdapter = $this->_getCasAdapter();
        $casAdapter->setTicket($this->_getParam('ticket'));
        
        if ($this->_getParam('logoutRequest')) {
            $casAdapter->handleLogoutRequest($this->_getParam('logoutRequest'));
        }
        
        try {
            $result = $auth->authenticate(new Unl_Auth_Adapter_Cas($casAdapter));
        } catch (Exception $e) {
            //
        }
        
        if ($result && $result->isValid()) {
            $this->_setupUser(Zend_Auth::getInstance()->getIdentity());
        } else {
            Zend_Auth::getInstance()->clearIdentity();
            if (isset($_COOKIE['unl_sso'])) {
                setcookie('unl_sso', 'fake', time() - 60*60*24, '/', '.unl.edu');
            }
        }
        
        $session = new Zend_Session_Namespace(__CLASS__);
        $this->_redirect($session->referer);
    }

    /**
     * Called after a user has successfully logged in.
     * Override this to do further setup of the user's session.
     * @param string $username
     */
    protected function _setupUser($username) {}
    
    /**
     * Called immediately before a user logs out.
     * Override this to do further tear down of a user's session.
     * @param string $username
     */
    protected function _destroyUser($username) {}
    
    /**
     * Returns the default landing page a user is sent to after logging in.
     * Override this if you don't want users sent to the site root after logging in.
     */
    protected function _getDefaultLandingPath()
    {
        return '/';
    }
    
    /**
     * Sets up the CAS adapter and returns it.
     * Overide this if you need to initialize the CAS adapter with different settings.
     * @return Unl_Cas
     */
    protected function _getCasAdapter()
    {
        static $adapter = NULL;
        
        if (!$adapter) {
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
                $serviceUrl = 'https://';
            } else {
                $serviceUrl = 'http://';
            }
            $path = Zend_Controller_Front::getInstance()->getRouter()->assemble(array(
                'module' => $this->getRequest()->getModuleName(),
                'controller' => $this->getRequest()->getControllerName(),
                'action' => 'cas'
            ));
            $serviceUrl .= $_SERVER['SERVER_NAME'] . $path;
            $adapter = new Unl_Cas($serviceUrl, 'https://login.unl.edu/cas');
        }
        
        return $adapter;
    }
}