<?php

namespace t41\ObjectModel;

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
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 870 $
 */

use t41\ObjectModel;
use t41\Backend;
use t41\ObjectModel\Property\PropertyAbstract;
use t41\ObjectModel\Property\IdentifierProperty;

/**
 * Class for a collection of Objects
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Collection extends ObjectModelAbstract {

	
	const CALC_SUM	= 1;
	
	const CALC_AVG	= 2;
	
	
	const POS_FIRST	= 1;
	
	const POS_LAST	= 2;
	
	
	/**
	 * @var t41_Data_Object
	 */
	protected $_do;
	
	
	protected $_members = array();
	
	
	protected $_offset = 0;
	
	
	protected $_batch = 10;
	
	
	protected $_max;
	
	
	/**
	 * Array of t41_Condition objects
	 * 
	 * @var array
	 */
	protected $_conditions = array();
	
	
	/**
	 * Array of sorting arrays (index 0: t41_Property_Interface object, index 1: ASC or DESC)
	 * @var unknown_type
	 */
	protected $_sortings = array();
	

	/**
	 * This class is used for manipulation of collection
	 * 
	 * Possible parameters are:
	 * - memberType: [uri|data|model] 
	 *   Defines which type of members we expect to get returned from backend.
	 *   uri:   returns t41_Object_Uri references
	 *   data:  returns populated t41_Data_Object instances
	 *   model: returns populated t41_Object_Model-based instances
	 *   default value is data
	 * 
	 * @param t41_Data_Object $do
	 * @param array $params
	 */
	public function __construct(ObjectModel\DataObject $do, array $params = null)
	{
		/* deal with class parameters first */
		$this->_setParameterObjects();
		
		if (is_array($params)) {
			
			$this->_setParameters($params);
		}
		
		$this->_do = $do;
	}
	
	
	
	public function addMember($object)
	{
		if (get_class($object) != $this->_do->getClass()) {
			
			throw new Exception(array('VALUE_MUST_BE_INSTANCEOF', $this->_do->getClass()));
		}
		
		$this->_members[] = $object;
	}
	
	
	public function setCondition(PropertyInterface $property, $value = null, $operator = null, $mode = 'AND')
	{
		$condition = new Backend\Condition($property
									,  isset($value) ? $value : null
									,  isset($operator) ? $operator : Backend\Condition::OPERATOR_EQUAL
									  );
		
		$this->_conditions[] = array($condition, $mode);
		
		return $condition;
	}
	
	
	public function setSorting($property, $order = 'ASC')
	{
		if (! $property instanceof Property\PropertyInterface) {
			
			if (! is_array($property)) {
				
				throw new Exception("First parameter must be either a t41_Property_* instance or an array");
			}
			
			$order = isset($property[1]) ? $property[1] : 'ASC';
			
			$property = $this->_do->getProperty($property[0]);
			
			if (! $property instanceof Property\PropertyInterface) {
				
				throw new Exception("PARAM_DOESNT_MATCH_PROPERTY");
			}
		}
		
		$this->_sortings[] = array($property, $order);
		
		return $this;
	}
	
	
	public function setSortings(array $sortings)
	{
		foreach ($sortings as $sorting) {
			
			$this->setSorting($sorting[0], $sorting[1]);
		}
		
		return $this;
	}
	
	
	public function setBoundaryOffset($offset)
	{
		$this->_offset = $offset;
		
		return $this;
	}
	
	
	public function setBoundaryBatch($batch)
	{
		$this->_batch = $batch;
		
		return $this;
	}
	
	
	public function getMax()
	{
		if (is_null($this->_max)) {
			
			$this->_count();
		}
		
		return $this->_max;
	}
	
	
	public function getDataObject()
	{
		return $this->_do;
	}
	
	
	public function getConditions()
	{
		return $this->_conditions;
	}
	
	
	public function getSortings()
	{
		return $this->_sortings;
	}
	
	
	public function getBoundaryOffset()
	{
		return $this->_offset;
	}
	
	
	public function getBoundaryBatch()
	{
		return $this->_batch;
	}
	
	
	public function getClass()
	{
		return $this->_do->getClass();
	}
	
	
	public function find(\t41\Backend\Adapter\AdapterInterface $backend = null)
	{
		if (is_null($backend)) $backend = ObjectModel::getObjectBackend($this->_do->getClass());
		$this->_members = (array) \t41\Backend::find($this, $backend);
	}
	
	
	public function getMember($pos = null)
	{
		if (! is_array($this->_members) || count($this->_members) == 0) {

			return false;
		}
		
		switch ($pos) {

			case self::POS_FIRST:
				return $this->_members[0];
				break;
				
			case self::POS_LAST:
				return $this->_members[count($this->_members)-1];
				break;
				
			default:
				return next($this->_members);
				break;
		}
	}
	
	
	public function getMembers()
	{
		if (! is_array($this->_members)) {
			
			Backend::find($this);
		}
		
		return $this->_members;
	}
	

	public function getTotalMembers()
	{
		return is_array($this->_members) ? count($this->_members) : null;
	}
	
	
	public function getProperties()
	{
		return $this->_do->getProperties();
//		return $this->_do->getPropertiesAsElements();
	}
	

	protected function _count($backend = null)
	{
		if (is_null($backend)) $backend = \t41\Backend::getDefaultBackend();
		$this->_max = (integer) \t41\Backend::find($this, $backend, true);
	}
	
	
	/**
	 * Set a new condition on property given id or throws an exception if property doesn't exist
	 *  
	 * @param string $propertyName
	 * @param return t41_Condition
	 * @throws t41_Exception
	 */
	public function having($propertyName)
	{
		if ($propertyName == ObjectUri::IDENTIFIER) {
			
			return $this->setCondition(new Property\IdentifierProperty('id'));
			
		} else if (($property = $this->_do->getProperty($propertyName)) !== false) {

			return $this->setCondition($property);
		}
		
		throw new Exception(array("CONDITION_UNKNOWN_PROPERTY", $propertyName));
	}
	
	
	
	public function returnsDistinct($string, $backend = null)
	{
		$prop = $this->_do->getProperty($string);
		if (! $prop instanceof Property\PropertyAbstract) {
			
			throw new Exception("unknown property: " . $string);
		}
		
		if (is_null($backend)) $backend = ObjectModel::getObjectBackend($this->_do->getClass());
		return (array) \t41\Backend::returnsDistinct($this, $prop, $backend);
	}
	
	/*
	 * Capture unknown methods and try to match them with some shortcut patterns:
	 * 
	 * havingXX(): shortcut to having()
	 * getXX(): compute calculation on collection members properties
	 * 
	 * @param string $m called method name
	 * @param array  $a called method arguments
	 * @return t41_Condition
	 * @throws t41_Object_Exception
	 */
	public function __call($m, $a)
	{
		if (substr($m, 0, 6) == 'having') {

			/* set a new condition with a call to having<<PropertyId>>() */
			$prop = strtolower(substr($m, 6));

			return $this->having( empty($a) ? $prop : $prop . '.' . $a);
				
		} else if (substr($m, 0, 3) == 'get') {
			
			$prop = strtolower(substr($m, 3));
			$calc = 0;

			if (isset($a[0])) {
				
				// @todo count $a members
				switch ($a[0]) {
				
					/* sum up the values of all members $prop property */
					case self::CALC_SUM:
						foreach ($this->_members as $member) {
							
							$calc += (float) $member->getProperty($prop);
						}
						break;
					
					/* sum up the values of all members $prop property and average the result */
					case self::CALC_AVG:
						$calc = $this->__call($m, array(self::CALC_SUM));
						$calc = ($calc / count($this->_members));
						break;
				}
			}
			
			return $calc;
			
		} else {

			throw new Exception(array("OBJECT_UNKNOWN METHOD", $m));
		}
	}
}