<?php

class Unl_Date_Null extends Zend_Date
{
	protected $_displayString = 'Unknown';
	
	public function __construct($date = null, $part = null, $locale = null)
	{
		parent::__construct(0, $part, $locale);
		
		if ($date) {
			$this->_displayString = $date;
		}
	}
	
	public function toString($format = null, $type = null, $locale = null)
	{
		return $this->_displayString;
	}
}
