<?php

class Unl_Util
{
	/**
	 * Checks to see if the value is either an array or an SPL Iterator object
	 *
	 * @param mixed $value
	 * @return bool
	 */
	static function isArray($value)
	{
		return (bool) (is_array($value) || $value instanceof Iterator);
	}
}
