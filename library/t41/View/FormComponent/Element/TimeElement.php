<?php

namespace t41\View\FormComponent\Element;

/**
 * t41 Toolkit
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.t41.org/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@t41.org so we can send you a copy immediately.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 876 $
 */

use t41\Parameter;
use t41\ObjectModel\Property\TimeProperty;

/**
 * t41 Data Object handling a set of properties tied to an object
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class TimeElement extends AbstractElement {

	
	const TODAY = '___NOW___';
	
	
	public function __construct($id = null, array $params = null, $backend = null)
	{
		$this->_setParameterObjects(array(	'enable_quickset'		=> new Parameter(Parameter::BOOLEAN, true))
		);
		
		parent::__construct($id, $params, $backend);
		
	}
	

	public function getEnumValues($part)
	{
		$array = array();
	
		switch ($part) {
				
			case TimeProperty::HOUR_PART:
				$min = $this->getConstraint('hourmin') ? $this->getConstraint('hourmin') : 0;
				$max = $this->getConstraint('hourmax') ? $this->getConstraint('hourmax') : 23;
				for ($i = $min ; $i <= $max ; $i++) {
					$val = str_pad($i, 2, '0', STR_PAD_LEFT);
					$array[$val] = $val;
				}
				break;
	
			case TimeProperty::MIN_PART:
				$interval = $this->getConstraint('minuterange') ? $this->getConstraint('minuterange') : 1;
				$i = 0;
				while ($i < 60) {
					$val = str_pad($i, 2, '0', STR_PAD_LEFT);
					$array[$val] = $val;
					$i += $interval;
				}
				break;
	
			case TimeProperty::SEC_PART:
				for ($i = 0 ; $i < 60 ; $i++) {
					$array[$i] = str_pad($i, 2, '0');
				}
				break;
		}
	
		return $array;
	}
	
	
	public function getValue($part = null)
	{
		if (is_null($part)) {
			return $this->_value;
		}
		$parts = explode(':', $this->_value);
	
		switch ($part) {
			case TimeProperty::HOUR_PART:
				return $parts[0];
				break;
	
			case TimeProperty::MIN_PART:
				return isset($parts[1]) ? $parts[1] : '00';
				break;
	
			case TimeProperty::SEC_PART:
				return isset($parts[2]) ? $parts[2] : '00';
				break;
	
			default:
				return $this->_value;
				break;
		}
	}
	
	
	public function setDefaultValue($val)
	{
		 $this->_defaultVal = ($val == self::TODAY) ? date('H:i') : $val;
	}
	
	
	public function formatValue($str = null, $fancy = false)
	{
		 if (! is_null($str)) {
		 	$date = new \Zend_Date($str);
		 	return $fancy ? $date->toString(\Zend_Date::DATE_LONG) : $date->toString('dd/MM/yyyy');
			
		} else {
			
			return $str;
		}
	}
}
