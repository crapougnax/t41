<?php

namespace t41\Backend;

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
 * @version    $Revision: 839 $
 */

use t41\ObjectModel,
	t41\ObjectModel\ObjectUri,
	t41\ObjectModel\Property;

/**
 * Simple class to handle query conditions on objects collections
 * 
 * each instance refers to a t41_Property_Abstract passed as reference
 * and may contains several different clauses (value/operator pairs)
 * 
 * object instance may also contain another condition instance, especially
 * when the referenced property is handling an object reference. 
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
use t41\DataObject;

class Condition {
	
	
	/**
	 * Empty Value descriptor
	 * @var string
	 */
	const NO_VALUE	= 'IS NULL';
	
	/**
	 * OR mode descriptor
	 * @var string
	 */
	const MODE_OR				= 'OR';
	

	/**
	 * AND mode descriptor
	 * @var string
	 */
	const MODE_AND				= 'AND';
	
	
	
	/**
	 * "Greater than" operator 
	 * 
	 * @var integer
	 */
	const OPERATOR_GTHAN 		= 1;
	
	
	/**
	 * "Lower than" operator
	 * 
	 * @var integer
	 */
	const OPERATOR_LTHAN 		= 2;
	
	
	/**
	 * "Equals" operator
	 * 
	 * @var integer
	 */
	const OPERATOR_EQUAL 		= 4;
	
	
	/**
	 * "Different from" operator
	 * 
	 * @var integer
	 */
	const OPERATOR_DIFF  		= 8;
	
	
	/**
	 * "Begins with" operator
	 * 
	 * @var integer
	 */
	const OPERATOR_BEGINSWITH	= 16;
	
	
	/**
	 * "End with" operator
	 * 
	 * @var integer
	 */
	const OPERATOR_ENDSWITH		= 32;
	
	
	/**
	 * Property object instance
	 * 
	 * @var t41_Property_Interface
	 */
	protected $_property;
	
	
	/**
	 * Recursive condition (condition applied to a property of the condition property)
	 * 
	 * @var t41_Condition
	 */
	protected $_condition;
	
	
	
	/**
	 * Array of clauses
	 * 
	 * <ul>
	 * <li>value with key 'value' contains value to compare</li>
	 * <li>value with key 'operator' contains either a combination of above operators or a literal string</li> 
	 * <li>if empty, constant OPERATOR_EQUALS is default value</li>
	 * </ul>
	 * 
	 * @var array
	 */
	protected $_clauses = array();
	
	

	/**
	 * Current clause key value
	 * 
	 * @var integer
	 */
	protected $_current = 0;
	
	
	/**
	 * Class constructor
	 * 
	 * Property reference and value and operator of first clause may be defined here.
	 * It is also possible to define all elements at a later state with the relevant set() method.
	 * Clause is only defined upon the presence of a value.
	 *  
	 * @param t41_Property Interface $property		property instance to enforce condition on
	 * @param mixed $value
	 * @param integer|string $operator
	 */
	public function __construct(ObjectModel\Property\AbstractProperty $property = null, $value = null, $operator = self::OPERATOR_EQUAL)
	{
		if (! is_null($property)) {

			$this->setProperty($property);
		}
		
		if (! is_null($value)) {

			$this->setValue($value);
		
			if (! is_null($operator)) {
			
				$this->setOperator($operator);
			}
			
			$this->_current++;
		}
	}

	
	/**
	 * Sets the property instance on which to enforce condition
	 * 
	 * @param t41_Property_Interface $property
	 * @return t41_Condition
	 */
	public function setProperty(\t41\ObjectModel\Property\AbstractProperty $property)
	{
		$this->_property = $property;
		return $this;
	}
	
	
	/**
	 * Sets the value to compare to property
	 * 
	 * @param mixed $value
	 * @return t41\Backend\Condition
	 */
	public function setValue($value, $increment = false)
	{
		$this->_clauses[$this->_current]['value'] = $value;
		if ($increment) {
			$this->_current++;
		}
		return $this;
	}
	

	/**
	 * Sets the comparison operator, can be either a constant (or a combination of constants) 
	 * from this class or a literal operator 
	 * 
	 * @param integer|string $operator
	 * @return t41\Backend\Condition
	 */
	public function setOperator($operator, $increment = false)
	{
		$this->_clauses[$this->_current]['operator'] = $operator;
		if ($increment) {
			$this->_current++;
		}
		return $this;
	}
	
	
	/**
	 * Returns the property instance
	 * 
	 * @return t41\ObjectModel\Property\AbstractProperty
	 */
	public function getProperty()
	{
		return $this->_property;
	}
		
	
	/**
	 * Returns the current nested t41_Condition child instance
	 * or null
	 * 
	 * @return t41\Backend\Condition
	 */
	public function getCondition()
	{
		return $this->_condition;
	}
	
	
	/**
	 * Returns an array of clauses to enforce on the property
	 * 
	 * @return array
	 */
	public function getClauses()
	{
		return $this->_clauses;
	}
	

	/**
	 * Returns true if the current instance contains a child instance
	 * 
	 * @return boolean
	 */
	public function isRecursive()
	{
		return ($this->_condition instanceof Condition);
	}
	
	
	/**
	 * Set a new condition on property given id or throws an exception if property doesn't exist
	 *  
	 * @param string $name
	 * @param return t41_Condition
	 * @throws t41_Exception
	 */
	public function having($name)
	{
		// condition on the identifier part of the uri
		if ($name == ObjectUri::IDENTIFIER) {
			
			$this->_condition = new self(new Property\IdentifierProperty(ObjectUri::IDENTIFIER));
			return $this->_condition;
			
		} else if (! $this->_property instanceof Property\ObjectProperty && ! $this->_property instanceof Property\CollectionProperty) {

			throw new Exception("CONDITION_INCORRECT_PROPERTY");
		}
		
		$do = ObjectModel\DataObject::factory($this->_property->getParameter('instanceof'));
		
		if (($property = $do->getProperty($name)) !== false) {

			$this->_condition = new self($property);
			return $this->_condition;
		}
		
		throw new Exception(array("CONDITION_UNKNOWN_PROPERTY", $name));
	}
	
	
	/**
	 * Set a new clause 
	 * 
	 * @param mixed $value
	 * @param mixed $operator
	 * @return t41_Condition
	 */
	public function where($value, $operator, $mode = self::MODE_AND)
	{
		// clause is added and counter is incremented
		return $this->setValue($value)
					->setMode($mode)
					->setOperator($operator, true);
	}
	
	
	/**
	 * Set a new 'is equal to' clause
	 * 
	 * @param mixed $value
	 * @return t41_Condition
	 */
	public function equals($value, $mode = self::MODE_AND)
	{
		return $this->where($value, self::OPERATOR_EQUAL, $mode);
	}
	
	
	/**
	 * Set a new 'is different from' clause 
	 * @param mixed $value
	 * @return t41_Condition
	 */
	public function notEquals($value)
	{
		return $this->where($value, self::OPERATOR_DIFF);
	}
	
	
	/**
	 * Set a new 'is greater than' clause
	 * 
	 * @param mixed $value
	 * @return t41_Condition
	 */
	public function greater($value)
	{
		return $this->where($value, self::OPERATOR_GTHAN);
	}
	
	
	/**
	 * Set à new 'is greater than or equal to' clause
	 * 
	 * @param mixed $value
	 * @return t41_Condition
	 */
	public function greaterOrEquals($value)
	{
		return $this->where($value, self::OPERATOR_GTHAN | self::OPERATOR_EQUAL);
	}

	
	/**
	 * Set a new 'is lower than' clause
	 * 
	 * @param mixed value
	 * @return t41_Condition
	 */
	public function lower($value)
	{
		return $this->where($value, self::OPERATOR_LTHAN);
	}
	

	/**
	 * Set a new 'lower than or equals to' clause
	 * 
	 * @param mixed value
	 * @return t41_Condition
	 */
	public function lowerOrEquals($value)
	{
		return $this->where($value, self::OPERATOR_LTHAN | self::OPERATOR_EQUAL);
	}
	

	/**
	 * Set a new 'lower than x and greater than y or equals to x or y' clause
	 * 
	 * @param integer $floor
	 * @param integer $ceil
	 * @return t41_Condition
	 */
	public function between($floor, $ceil)
	{
		return $this->where($floor, self::OPERATOR_GTHAN | self::OPERATOR_EQUAL)
					->where($ceil, self::OPERATOR_LTHAN | self::OPERATOR_EQUAL);
	}
	
	
	/**
	 * Set  a new 'begins with' clause
	 * 
	 * @param string $value
	 * @return t41_Condition
	 */
	public function beginsWith($value)
	{
		return $this->where($value, self::OPERATOR_BEGINSWITH | self::OPERATOR_EQUAL);
	}
	
	
	/**
	 * Set a new 'ends with' clause
	 * 
	 * @param string $value
	 * @return t41_Condition
	 */
	public function endsWith($value)
	{
		return $this->where($value, self::OPERATOR_ENDSWITH | self::OPERATOR_EQUAL);
	}
	
	
	/**
	 * Set a new 'contains' clause
	 * 
	 * @param string $value
	 * @return t41_Condition
	 */
	public function contains($value)
	{
		return $this->where($value, self::OPERATOR_BEGINSWITH | self::OPERATOR_ENDSWITH | self::OPERATOR_EQUAL);
	}
	
	
	/**
	 * Prepare next clause to use OR mode
	 * @return t41_Condition
	 */
	public function orMode()
	{
		$this->_clauses[$this->_current]['mode'] = self::MODE_OR;
		return $this;
	}
	
	
	/**
	 * Prepare next clause to use AND mode (default behavior)
	 * @return t41_Condition
	 */
	public function andMode()
	{
		$this->_clauses[$this->_current]['mode'] = self::MODE_AND;
		return $this;
	}
	

	public function setMode($mode)
	{
		$this->_clauses[$this->_current]['mode'] = $mode;
		return $this;
	}
}
