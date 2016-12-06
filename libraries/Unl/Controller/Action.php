<?php

require_once ('Zend/Controller/Action.php');

class Unl_Controller_Action extends Zend_Controller_Action
{
    protected $_authorize;

	public function init()
	{
		parent::init();
		if ($this->view) {
            $this->view->addHelperPath(dirname(__FILE__) . '/../View/Helper', 'Unl_View_Helper');
		}
		Zend_Controller_Action_HelperBroker::addPrefix('Unl_Controller_Helper');
		$this->_authorize = $this->_helper->getHelper('Authorize');
	}
    
    protected function _disableLayoutAndView()
    {
        $this->_helper->layout->disableLayout();
        $this->getFrontController()->setParam('noViewRenderer', true);
    }
}

