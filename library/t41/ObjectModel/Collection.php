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
 * @copyright  Copyright (c) 2006-2013 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

use t41\Backend;
use t41\Backend\Adapter;
use t41\Backend\Condition;

use t41\ObjectModel;
use t41\ObjectModel\Property\AbstractProperty;
use t41\ObjectModel\Property\ObjectProperty;
use t41\ObjectModel\Property\IdentifierProperty;
use t41\ObjectModel\Collection\StatsCollection;
use t41\Backend\Condition\Combo;

/**
 * Class for a collection of Objects
 *
 * @category   t41
 * @package    t41_ObjectModel
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Collection extends ObjectModelAbstract {

	
	const MEMBER_APPEND 	= 'append';
	
	const MEMBER_PREPEND	= 'prepend';
	
	const MEMBER_REMOVE		= 'remove';
	
	
	const POS_FIRST	= 'first';
	
	const POS_LAST	= 'last';
	
	
	/**
	 * @var t41\ObjectModel\DataObject
	 */
	protected $_do;
	
	
	protected $_members = array();
	
	
	public $status;
	
	
	/**
	 * 
	 * @var t41\ObjectModel\Property\AbstractProperty
	 */
	protected $_parent;
	
	
	/**
	 * Array of members awaiting saving or deletion
	 * @var array
	 */
	protected $_spool = array('save' => array(), 'delete' => array());
	
	
	protected $_offset = 0;
	
	
	protected $_batch = 10;
	
	
	protected $_max;
	
	
	protected $_lastSubFind;
	
	
	protected $_latestBackend;
	
	
	/**
	 * Array of t41\Backend\Condition objects
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
	 * @param \t41\ObjectModel\DataObject|string $do
	 * @param array $params
	 */
	public function __construct($do, array $params = null)
	{
		if ($do instanceof ObjectModel\DataObject) {
			$this->_do = $do;
		} else if (is_string ($do)) {
			$this->_do = ObjectModel\DataObject::factory($do);
		} else {
			throw new Exception("Collection must be instanced from data object or class name");
		}
		
		/* deal with class parameters first */
		$this->_setParameterObjects();
		
		if (is_array($params)) {
			$this->_setParameters($params);
		}
	}
	
	
	/**
	 * Defines collection parent if exists
	 * @param AbstractProperty $prop
	 * @return \t41\ObjectModel\Collection
	 */
	public function setParent(AbstractProperty $prop)
	{
		$this->_parent = $prop;
		return $this;
	}
	
	
	/**
	 * Return previously defined collection parent if exists
	 * @return \t41\ObjectModel\Property\AbstractProperty
	 */
	public function getParent()
	{
		return $this->_parent;
	}
	
	
	public function getCachePrefix()
	{
		$str = '';
		foreach ($this->_conditions as $condition) {
			$str .= '-' . print_r($condition, true);
		}
		return md5($this->_do->getClass() . $str);
	}
	
	
	/**
	 * Add a member to the collection, passed object must be instance of class defined in dataobject
	 * 
	 * @param ObjectModel\BaseObject $object
	 * @param string $position
	 * @throws Exception
	 * @return \t41\ObjectModel\Collection
	 */
	public function addMember(ObjectModel\BaseObject $object, $position = null)
	{
		$class = $this->getClass();
		if (! $object instanceof $class) {
			throw new Exception(array('NOT_INSTANCEOF', array(get_class($object),$class)));
		}

		if ($position == self::MEMBER_PREPEND) {
			array_unshift($this->_members, $object);
			
		} else {
			// @todo add instant saving of unsaved members before any find()
			$this->_members[] = $this->_spool['save'][] = $object;
		}
		
		// protect against any find() call before members are saved
		$this->setParameter('populated', true);
		$this->_max = count($this->_members);
		
		return $this;
	}
	
	
	/**
	 * Remove the given member from the collection
	 * Returns true if success, false otherwise
	 * 
	 * @param ObjectModel\BaseObject $object
	 * @return boolean
	 */
	public function removeMember(ObjectModel\BaseObject $object)
	{
		$uri = $object instanceof ObjectUri ? $object->__toString() : $object->getUri()->__toString();
		foreach ($this->getMembers(ObjectModel::MODEL) as $key => $member) {
			if ($member === $object || $member->getUri()->__toString() == $uri) {
				$this->_spool['delete'][] = $member;
				$this->_members[$key] = null;
				unset($this->_members[$key]);
				return true;
			}
		}
		return false;
	}
	
	
	/**
	 * Returns a new instance of a member
	 * @return t41\ObjectModel\BaseObject
	 */
	public function newMember()
	{
		return ObjectModel::factory($this->_do->getClass());
	}
	
	
	/**
	 * Add a property dynamically to the data object of reference
	 * @param AbstractProperty $property
	 * @return t41\ObjectModel\Collection
	 */
	public function addProperty(AbstractProperty $property)
	{
		$this->_do->addProperty($property);
		return $this;
	}
	
	
	public function setCondition(Property\AbstractProperty $property, $value = null, $operator = null, $mode = 'AND')
	{
		$condition = new Backend\Condition($property
										,  isset($value) ? $value : null
										,  isset($operator) ? $operator : Condition::OPERATOR_EQUAL
									  	  );
		
		$this->_conditions[] = array($condition, $mode);
		
		return $condition;
	}
	
	
	/**
	 * Return a new Combo object bound to the current collection
	 * @param string $mode
	 * @return \t41\Backend\Condition\Combo
	 */
	public function setConditionCombo($mode)
	{
		$combo = new Condition\Combo($this);
		$this->_conditions[] = array($combo, $mode);
		return $combo;
	}
	
	
	/**
	 * Reset all conditions matching the given property id
	 * Limitation: combos are ignored 
	 * @param string $property
	 * @return t41\ObjectModel\Collection current instance
	 */
	public function resetConditions($property)
	{
		// temp fix - @todo recursion
		if (strstr($property, '.') !== false) {
			$parts = explode('.', $property);
			$property = $parts[0];
		}
		
		foreach ($this->_conditions as $key => $condition) {
			if (! $condition[0] instanceof Combo && $condition[0]->getProperty()->getId() == $property) {
				unset($this->_conditions[$key]);
			}
		}
		
		$this->setParameter('populated', false);
		$this->_members = null;
		
		return $this;
	}
	
	
	public function resetSortings($property)
	{
		$this->_sortings = array();
	}
	
	
	/**
	 * Sets a new sorting rule which is added to the previous, if any 
	 * @param array|t41\ObjectModel\Property\AbstractProperty $property
	 * @param string $order
	 * @param string $modifier
	 * @throws Exception
	 */
	public function setSorting($property, $order = 'ASC', $modifier = null)
	{
		if (! $property instanceof Property\PropertyInterface) {
			$parent = substr($property[0], 0, strpos($property[0],'.'));
			if (! is_array($property)) {
				throw new Exception('First parameter must be either a t41\ObjectModel\Property\AbstractProperty-derived instance or an array');
			}

			$order = isset($property['mode']) ? $property['mode'] : isset($property[1]) ? $property[1] : 'ASC';
			$modifier = isset($property[2]) ? $property[2] : null;
				
			if ($property[0] == ObjectUri::IDENTIFIER) {
				$property = new IdentifierProperty(ObjectUri::IDENTIFIER);
			} else {
				$property = $this->_do->getRecursiveProperty(isset($property['property']) ? $property['property'] : $property[0]);
			}
					
			if (! $property instanceof Property\PropertyInterface) {
				throw new Exception("PARAM_DOESNT_MATCH_PROPERTY");
			}
		}
		$this->_sortings[] = array($property, $order, $modifier, isset($parent) ? $parent : null);
		return $this;
	}
	
	
	public function setSortings(array $sortings)
	{
		foreach ($sortings as $sorting) {
			$this->setSorting($this->_do->getRecursiveProperty($sorting[0]), $sorting[1], isset($sorting[2]) ? $sorting[2] : null);
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
			$this->_count($this->_latestBackend);
		}
		return $this->_max;
	}
	
	
	/**
	 * Returns the collection data object reference
	 * @return t41\ObjectModel\DataObject
	 */
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
	
	
	/**
	 * Execute a query on given, object's or default backend with the current conditions
	 * 
	 * @param string $memberType Dynamically change members type
	 * @param Adapter\AbstractAdapter $backend specific backend to use
	 */
	public function find($memberType = null, Adapter\AbstractAdapter $backend = null)
	{
		if ($memberType) {
			$prevMemberType = $this->getParameter('memberType');
			$this->setParameter('memberType', $memberType);
		}
		
		if (is_null($backend)) $backend = $this->_latestBackend;
		if (is_null($backend)) $backend = ObjectModel::getObjectBackend($this->_do->getClass());
		$this->_latestBackend = $backend;
		$this->_count($backend);
		
		if ($this->_max > 0) {
			
			if ($memberType) {
				$this->setParameter('memberType', $prevMemberType);
			}
			Backend::find($this, $backend);
			$this->_members = Backend::find($this, $backend);
			$this->setParameter('populated', true);
			return (integer) $this->_max;
		} else {
			return false;
		}
	}
	
	
	/**
	 * Execute a query on the current members list and return the matching ones or their quantity
	 * @param array $conditions
	 * @param boolean $returnCount
	 * @return array|integer
	 */
	public function subFind(array $conditions = array(), $returnCount = false)
	{
		$this->_lastSubFind = null;
		
		if (! is_array($this->_members)) {
			return $returnCount ? 0 : array();
		}
		
		$subArray = $this->_castMembers($this->_members, ObjectModel::MODEL);
		
		foreach ($conditions as $propertyName => $propertyVal) {
			if ($propertyVal instanceof ObjectModel\BaseObject || $propertyVal instanceof ObjectModel\DataObject  || $propertyVal instanceof ObjectModel\ObjectUri) {
				$propertyVal = $propertyVal->getIdentifier();
			}
			
			foreach ($subArray as $key => $val) {
				$memberProp = $val->getProperty($propertyName);
				$val = $memberProp->getValue();
				if ($val instanceof ObjectModel\BaseObject || $val instanceof ObjectModel\DataObject || $val instanceof ObjectModel\ObjectUri) {
					$val = $val->getIdentifier();
					
					if (! $val) {
						unset($subArray[$key]);
						continue;
					}
				}
				
				if ($val != $propertyVal) {
					unset($subArray[$key]);
				}
			}
		}
		
		sort($subArray);
		$this->_lastSubFind = $subArray;
		
		return $returnCount ? count($subArray) : $subArray;
	}
	
	
	/**
	 * Return the total number of members grouped by the given property or properties
	 * 
	 * @todo move code below in StatsCollection()
	 * @param unknown_type $properties
	 * @param Adapter\AbstractAdapter $backend
	 * @return t41\ObjectModel\StatsCollection
	 */
	public function stats($properties, Adapter\AbstractAdapter $backend = null)
	{
		if (! is_array($properties)) $properties = (array) $properties;
		
		foreach ($properties as $key => $prop) {
			// retrieve actual properties from their id
			if (($prop = $this->_do->getRecursiveProperty($prop)) !== false) {
				$properties[$key] = $prop;
			} else {
				unset($properties[$key]);
			}
		}
		
		if (is_null($backend)) $backend = $this->_latestBackend;
		if (is_null($backend)) $backend = ObjectModel::getObjectBackend($this->_do->getClass());
		$res = Backend::find($this, $backend, $properties);
		//\Zend_Debug::dump(Backend::getLastQuery());
		$this->_latestBackend = $backend;
		$this->_max = null;
		
		$stats = new StatsCollection();
		$stats->setStatsProps($properties);
		
		foreach ($res as $row) {
			$member = $stats->newMember();
			$member->setTotal($row[Backend::MAX_ROWS_IDENTIFIER]);
			$array = array();
			foreach ($properties as $property) {
				if ($property instanceof ObjectProperty) {
					$class = $property->getParameter('instanceof');
					$array[$property->getId()] = new $class($row[$property->getId()]);
				} else {
					$array[$property->getId()] = $row[$property->getId()];
				}
			}
			$member->setGroup($array);
			$stats->addMember($member);
		}

		return $stats;
	}
	
	
	public function getLastSubFind($index = null)
	{
		return (array) ($index !== null) ? $this->_lastSubFind[$index] : $this->_lastSubFind;
	}
	
	
	/**
	 * Return a member from is numeric key
	 * @param integer $pos
	 * @param string $type
	 * @return boolean|\t41\ObjectModel\DataObject|\t41\ObjectModel\ObjectUri>|\t41\ObjectModel\BaseObject
	 */
	public function getMember($pos = null, $type = null)
	{
		if (! is_array($this->_members) || count($this->_members) == 0) {
			return false;
		}
		
		switch ($pos) {

			case self::POS_FIRST:
				$member = $this->_members[0];
				break;
				
			case self::POS_LAST:
				$member = $this->_members[count($this->_members)-1];
				break;
				
			default:
				// @todo should we return false if member is missing?
				$member = isset($this->_members[$pos]) ? $this->_members[$pos] : $this->_members[0];
				break;
		}
		
		return is_null($type) ? $member : $this->_castMember($member, $type);
	}
	
	
	public function getMemberFromUri($uri)
	{
		foreach ($this->_members as $member) {
			if ($member->getIdentifier() && $member->getIdentifier() == $uri) {
				return $this->_castMember($member, ObjectModel::MODEL);
			}
		}
		return false;
	}
	
	/**
	 * Return the members of the collection in the given format type or as ObjectModel if no type is specified
	 * Execute an implicit find() if collection is not populated yet
	 * @param string $type
	 * @return array
	 */
	public function getMembers($type = ObjectModel::MODEL)
	{
		// if collection has not been populated yet, do it now with $type as param
		if (! $this->getParameter('populated')) {
			$this->find($type);
			return $this->_members;
		}
		return $this->_castMembers($this->_members, $type);
	}
	

	/**
	 * Convert the given member to the given type
	 * @param object $member
	 * @param string $toType
	 * @return \t41\ObjectModel\DataObject|\t41\ObjectModel\ObjectUri>|\t41\ObjectModel\BaseObject
	 */
	protected function _castMember($member, $toType)
	{
		// Don't cast if it's already done
		if (($toType == ObjectModel::DATA && $member instanceof DataObject)
		|| ($toType == ObjectModel::MODEL && $member instanceof BaseObject)
		|| ($toType == ObjectModel::URI && $member instanceof ObjectUri)) {
			return $member;
		}
		
		$class = $this->getClass();
		
		switch ($toType) {
			
			case ObjectModel::DATA:
				if ($member instanceof BaseObject) {
					$member = $member->getDataObject();
				} else {
					$do = new DataObject($class);
					$do->setUri($member);
					Backend::read($do);
					$member = $do;
				}
				break;
				
			case ObjectModel::MODEL:
				$member = new $class($member);
				break;
				
			case ObjectModel::URI:
				$member = $member->getUri();
				break;
		}
		
		return $member;
	}
	
	
	protected function _castMembers(array $members, $toType)
	{
		foreach ($members as $key => $member) {
			$members[$key] = $this->_castMember($member, $toType);
		}
		return $members;
	}
	
	
	public function getTotalMembers()
	{
		return is_array($this->_members) ? count($this->_members) : null;
	}
	
	
	public function getProperties()
	{
		return $this->_do->getProperties();
	}
	

	/**
	 * Get total quantity of potential members meeting condition(s)
	 * @param Backend\Adapter\AbstractAdapter $backend
	 */
	protected function _count(Backend\Adapter\AbstractAdapter $backend = null)
	{
		if (is_null($backend)) $backend = ObjectModel::getObjectBackend($this->_do->getClass());
		if (is_null($backend)) $backend = Backend::getDefaultBackend();
		$this->_max = (integer) Backend::find($this, $backend, true);
	}
	
	
	public function count(Backend\Adapter\AbstractAdapter $backend = null)
	{
		$this->_count($backend);
		return $this->_max;
	}
	
	
	/**
	 * Set a new condition on property given id or throws an exception if property doesn't exist
	 *  
	 * @param string $propertyName
	 * @param return t41\Backend\Condition
	 * @throws t41\Backend\Exception
	 */
	public function having($propertyName, $mode = Condition::MODE_AND)
	{
		// reset $_max & populated
		$this->_max = null;
		$this->setParameter('populated', false);

		if (in_array($propertyName, array(Condition::MODE_AND, Condition::MODE_OR))) {
			return $this->setConditionCombo($propertyName);
		}
		
		// condition on the identifier part of the uri
		if ($propertyName == ObjectUri::IDENTIFIER) {
			return $this->setCondition(new Property\IdentifierProperty(ObjectUri::IDENTIFIER, null, null, $mode));
		} else if (strstr($propertyName, '.') !== false) {
			// deal with recursive properties
			$parts = explode('.', $propertyName);
			$condition = $this->setCondition($this->_do->getProperty($parts[0]));
				
			foreach (array_slice($parts,1) as $property) {
				$condition = $condition->having($property);
			}
			return $condition;
		} else if (($property = $this->_do->getRecursiveProperty($propertyName)) !== false) {
			return $this->setCondition($property, null, null, $mode);
		}
		
		throw new Backend\Exception(array("CONDITION_UNKNOWN_PROPERTY", $propertyName));
	}
	
	
	/**
	 * Return the number of distinct objects matching the given property
	 * @param string $string
	 * @param unknown_type $backend
	 * @throws Backend\Exception
	 * @return array
	 */
	public function returnsDistinct($string, $backend = null)
	{
		$prop = $this->_do->getProperty($string);
		if (! $prop instanceof Property\AbstractProperty) {
			throw new Backend\Exception(array("CONDITION_UNKNOWN_PROPERTY", $string));
		}
		
		if (is_null($backend)) $backend = ObjectModel::getObjectBackend($this->_do->getClass());
		return (array) Backend::returnsDistinct($this, $prop, $backend);
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
		
		} else if (substr($m, 0, 4) == 'stat') {
			
			$calc = 0;
				
			// populate or refresh collection, only if there is no member to save or delete
			if (count($this->_members) == 0 || (count($this->_spool['save']) == 0 && count($this->_spool['delete']) == 0)) {
				$this->find();
			}

			switch (substr($m,4)) {

				case 'total':
					return count($this->_members);
					break;
			}
			
		} else if (substr($m, 0, 4) == 'calc') {
			
			$prop = strtolower(substr($m, 4));
			$calc = 0;
			
			// populate or refresh collection, only if there is no member to save or delete
			if (count($this->_members) == 0 || (count($this->_spool['save']) == 0 && count($this->_spool['delete']) == 0)) {
				$this->find();
			}

			if (isset($a[0])) {
				
				// @todo count $a members
				switch ($a[0]) {
				
					/* sum up the values of all members $prop property */
					case ObjectModel::CALC_SUM:
						foreach ($this->_castMembers($this->_members, ObjectModel::DATA) as $member) {
							$property = $member->getProperty($prop);
							if (! $property instanceof Property\AbstractProperty) {
								continue;
							}
							$calc += (float) $property->getValue();
						}
						break;
					
					/* sum up the values of all members $prop property and average the result */
					case ObjectModel::CALC_AVG:
						$calc = $this->__call($m, array(self::CALC_SUM));
						$calc = ($calc / count($this->_members));
						break;
				}
			} else {
				throw new Exception("Missing mandatory argument for '$m' magic call");
			}
			return $calc;
			
		} else {
			throw new Exception(array("OBJECT_UNKNOWN METHOD", $m));
		}
	}
	
	
	/**
	 * Save all members marked as unsaved in given or default backend
	 * @param t41\Backend\Adapter\AbstractAdapter $backend
	 * @return boolean
	 */
	public function save(Backend\Adapter\AbstractAdapter $backend = null)
	{
		$res = true;
		
		foreach ($this->_spool['save'] as $key => $member) {
			$res2 = $member->save($backend);
			if ($res2 === true) {
				unset($this->_spool['save'][$key]);
			} else {
				$res = false;
			}
		}

		foreach ($this->_spool['delete'] as $key => $member) {
			$res2 = $member->delete($backend);
			if ($res2 === true) {
				unset($this->_spool['delete'][$key]);
			} else {
				$res = false;
			}
		}
		
		return $res;
	}
	
	
	public function debug()
	{
		$this->find();
		$this->setParameter('populated', false);
		\Zend_Debug::dump(Backend::getLastQuery());
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see t41\ObjectModel.ObjectModelAbstract::reduce()
	 */
	public function reduce(array $params = array(), $cache = true)
	{
		$data = array();
		
		// populate only collections if params is set
		if (isset($params['collections']) && $params['collections'] > 0) {
			
			$params['collections']--;
			foreach ($this->getMembers() as $member) {
				// but prevent too-costly any deeper recursion
				$data[] = $member->reduce($params, $cache);
			}
		}
		return array_merge(parent::reduce($params), array('collection' => $data));
	}
}
