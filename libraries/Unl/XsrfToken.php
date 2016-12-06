<?php

class Unl_XsrfToken
{
	/**
	 * The one true instance
	 * @var Unl_XsrfToken
	 */
    static protected $_instance;
    
    /**
     * Session storage, used to hold the tokens between requests.
     * @var unknown_type
     */
    protected $_session;

    /**
     * Protected to enforce the singleton nature of this class.
     * Sets up the session and removes any expired tokens.
     */
    protected function __construct()
    {
        $this->_session = new Zend_Session_Namespace('Unl_XsrfToken');
        if (!$this->_session->tokens) {
        	$this->_session->tokens = array();
        }
        
        foreach ($this->_session->tokens as $id => $token) {
        	if ($token['expires'] > 0 && $token['expires'] < time()) {
        		unset($this->_session->tokens[$id]);
        	}
        }
    }
    
    /**
     * Returns the singleton instance of the class.
     * @return Unl_XsrfToken
     */
    static public function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Creates a new token and stores it in the session (so you don't have to).
     * This token is meant to be used in a <input type="hidden" ...> element.
     * @param $lifetime How long the token is valid (in seconds). Default is the lifetime of the current session. 
     */
    public function create($lifetime = -1)
    {
    	if ($lifetime < 0) {
    		$expires = -1;
    	} else {
    		$expires = time() + $lifetime;
    	}
        $token = array(
            'expires' => $expires,
            'formUrl' => $_SERVER['SCRIPT_URI'],
            'remoteAddress' => $_SERVER['REMOTE_ADDR'],
            'userAgent' => $_SERVER['HTTP_USER_AGENT'],
        );
        $tokenId = base64_encode(hash('sha256', microtime(TRUE), TRUE));
        $this->_session->tokens[$tokenId] = $token;
        
        return $tokenId;
    }
    
    /**
     * Verifies that a token is valid, then destroys the token in the session.
     * @param $tokenId The token that was passed in with the form.
     * @param $onceOnly If set to FALSE, the token isn't destroyed.
     */
    public function verify($tokenId, $onceOnly = TRUE)
    {
        if (Zend_Registry::isRegistered('log') && Zend_Registry::get('log') instanceof Zend_Log) {
            $log = Zend_Registry::get('log');
        } else {
            $log = new Zend_Log();
            $log->addWriter(new Zend_Log_Writer_Null());
        }
        
    	$token = $this->_session->tokens[$tokenId];
    	if (!$token) {
    	    $log->log('XSRF: No token found.', Zend_Log::ERR);
    		return FALSE;
    	}
    	if ($token['remoteAddress'] != $_SERVER['REMOTE_ADDR']) {
    	    $log->log('XSRF: Remote address changed. (ignoring)', Zend_Log::ERR);
            //return FALSE;
    	}
    	if ($token['formUrl'] != $_SERVER['HTTP_REFERER']) {
    	    $log->log('XSRF: Referer doesn\'t match form url.', Zend_Log::ERR);
    		return FALSE;
    	}
    	if ($token['userAgent'] != $_SERVER['HTTP_USER_AGENT']) {
    	    $log->log('XSRF: User agent has changed.', Zend_Log::ERR);
    	    return FALSE;
    	}
    	
    	if ($onceOnly) {
            unset($this->_session->tokens[$tokenId]);
    	}
    	return TRUE;
    }
}