<?php

class Unl_Model_Collection implements Iterator, Countable, ArrayAccess
{

	/**
	 * name of the class of model this collection will be storing
	 */
	protected $_modelClass;

	/**
	 * an array of the models in this collection
	 */
	protected $_models = array();

	public function __construct($modelClass)
	{
		$this->_modelClass = $modelClass;
	}

	// Functions to implement the Iterator interface (allows foreach)

	public function current()
	{
		return current($this->_models);
	}

	public function key()
	{
		return key($this->_models);
	}

	public function next()
	{
		return next($this->_models);
	}

	public function rewind()
	{
		return reset($this->_models);
	}

	public function valid()
	{
		return (bool) $this->current();
	}

	// Function to implement the Countable interface (allows count())

	public function count()
	{
		return count($this->_models);
	}

	// Functions to implement ArrayAccess interface (allows $instance['key'])

	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->_models);
	}

	public function offsetGet($offset)
	{
		return $this->_models[$offset];
	}

	public function offsetSet($offset, $value)
	{
		if (!$value instanceof $this->_modelClass) {
			$className = get_class($value);
			if ($className) {
				throw new Exception('Cannot add an instance of "' . $className . '" to a collection of "' . $this->_modelClass . '" objects');
			} else {
				throw new Exception('Cannot add a non-ojbect to a collection of "' . $this->_modelClass . '" objects');
			}
		}

		if ($offset === null) {
			return ($this->_models[] = $value);
		} else {
			return ($this->_models[$offset] = $value);
		}
	}

	public function offsetUnset($offset)
	{
		unset($this->_models[$offset]);
	}

	public function merge(Unl_Model_Collection $collection, $reindex = FALSE)
	{
	    if ($reindex) {
	        $newModels = array();
	        foreach ($this->_models as $model) {
	            $newModels[] = $model;
	        }
	        foreach ($collection as $model) {
	            $newModels[] = $model;
	        }
	        $this->_models = $newModels;
	    } else {
            foreach ($collection as $model) {
                $modelId = $model->getId();
                $this->_models[$modelId] = $model;
            }
	    }
	}

	/**
	 * Removes and returns the last element from the collection
	 *
	 * @return Unl_Model
	 */
	public function pop()
	{
        return array_pop($this->_models);
	}

	public function getId()
	{
		$ids = array();
		foreach ($this->_models as $model) {
			$ids[] = $model->getId();
		}
		return $ids;
	}

	public function orderBy($method, $ascending = SORT_ASC)
	{
	    $valuesToSortOn = array();
	    $modelKeys = array();
	    foreach ($this->_models as $key => $model)
	    {
	        $valuesToSortOn[] = $model->$method();
	        $modelKeys[] = $key;
	    }
	    $valuesToSortOn = array_map('strtolower', $valuesToSortOn);
	    array_multisort($valuesToSortOn, $ascending, $modelKeys);
	    $sortedModels = array();
	    foreach ($modelKeys as $modelKey) {
	        $sortedModels[$modelKey] = $this->_models[$modelKey];
	    }
	    $this->_models = $sortedModels;
	}
	
	public function arrayFromMethod($method)
	{
	    $data = array();
	    foreach ($this->_models as $model)
	    {
	        $data[$model->getId()] = $model->$method();
	    }
	    return $data;
	}
}

