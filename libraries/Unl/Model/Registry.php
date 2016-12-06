<?php

class Unl_Model_Registry
{

	/**
	 * An array of collections for each type of model object stored
	 *
	 * @var arary
	 */
	protected $_collections = array();
	
	/**
	 * The one true instance
	 *
	 * @var Unl_Model_Registry
	 */
	protected static $_instance;
	
	/**
	 * Returns the one true instance
	 *
	 * @return Unl_Model_Registry
	 */
	public static function getInstance()
	{
		if (!self::$_instance) {
			self::$_instance = new self(); 
		}
		return self::$_instance;
	}
	
	protected function __construct()
	{
		//
	}
	
	/**
	 * Add a record to the Model Registry
	 *
	 * @param Unl_Model $record
	 */
	public function add(Unl_Model $record)
	{
		$className = get_class($record);
		$id = $record->getId();
		
		if (!$this->_collections[$className] instanceof Unl_Model_Collection) {
			$this->_collections[$className]  = new Unl_Model_Collection($className);
		} else if ($this->_collections[$className][$id] instanceof Unl_Model) {
			throw new Unl_Model_Registry_Exception('Cannot add record to Model Registry: duplicate record exists'); 
		}
		
		$this->_collections[$className][$id] = $record;
	}
	
	/**
	 * Get a Model from the Registry
	 *
	 * @param string $className
	 * @param int $id
	 * @return Unl_Model
	 */
	public function get($className, $id)
	{
		if (!$this->_collections[$className] instanceof Unl_Model_Collection) {
			return null;				
		}
		
		return $this->_collections[$className][$id];
	}
	
	/**
	 * Attempts to add the record to the registry.
	 * If a duplicate record exists, it is returned instead of an exception being thrown.
	 *
	 * @param Unl_Model $record
	 * @return Unl_Model
	 */
	public function getOrAdd(Unl_Model $record)
	{
		try {
			$this->add($record);
		} catch (Unl_Model_Registry_Exception $e) {
			$className = get_class($record);
			$id = $record->getId();
			$record = $this->get($className, $id);
		}
		
		return $record;
	}
	
	
}

