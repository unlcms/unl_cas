<?php

abstract class Unl_Model
{
	protected $_data = array();
	protected $_cleanData = array();
	
	protected function __construct($data)
	{
		$this->_data = $data;
	}
	
    abstract public function getId();
    
    protected function _setClean()
    {
    	$this->_cleanData = $this->_data;
    }
    
    
}
