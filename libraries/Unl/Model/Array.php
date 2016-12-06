<?php

class Unl_Model_Array extends Unl_Model 
{
	public function __construct($data)
	{
		$filteredData = array();
		foreach ($data as $key => $value) {
			$filteredData[strtolower($key)] = $value;
		}
		parent::__construct($filteredData);
	}
	
	public function getId()
	{
		return null;
	}
	
	public function __call($name, $arguments) {
		if (substr($name, 0, 3) == 'get') {
			$key = strtolower(substr($name, 3));
			return $this->_data[$key];
		}
		
		throw new Zend_Exception('Call to undefined method');
	}
}