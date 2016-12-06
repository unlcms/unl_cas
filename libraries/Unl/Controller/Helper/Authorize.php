<?php

class Unl_Controller_Helper_Authorize extends Zend_Controller_Action_Helper_Abstract
{
	public function requireLogin()
	{
		$user = Zend_Auth::getInstance()->getIdentity();
		if (!$user) {
			throw new Exception('You must be logged in to view this page.');
		}
	}

	public function isLoggedIn()
	{
        $user = Zend_Auth::getInstance()->getIdentity();
	    if ($user instanceof Auth_UserModel) {
	        return true;
	    }
	    return false;
	}
}
