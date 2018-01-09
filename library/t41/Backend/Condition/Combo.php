<?php

namespace t41\Backend\Condition;

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
 * @copyright  Copyright (c) 2006-2017 Quatrain Technologies SAS
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

use t41\ObjectModel\Property;
use t41\ObjectModel\ObjectUri;
use t41\ObjectModel\Collection;
use t41\Backend\Condition;
use t41\Backend\Exception;

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
 * @copyright  Copyright (c) 2006-2017 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Combo {
	
	protected $_collection;
	
	protected $do;
	
	/**
	 * Conditions array
	 * 
	 * @var array
	 */
	protected $_conditions;
	
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
	public function __construct(Collection $collection)
	{
		$this->_collection = $collection;
		$this->_do = $this->_collection->getDataObject();
	}

	
	/**
	 * Returns the current nested t41_Condition child instance
	 * or null
	 * 
	 * @return t41\Backend\Condition
	 */
	public function getConditions()
	{
		return $this->_conditions;
	}
	
	
	public function setCondition(Property\AbstractProperty $property, $value = null, $operator = null, $mode = 'AND')
	{
		$condition = new Condition($property
								,  isset($value) ? $value : null
								, isset($operator) ? $operator : Condition::OPERATOR_EQUAL
									);
	
		$this->_conditions[] = array($condition, $mode);
	
		return $condition;
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
		if ($propertyName == ObjectUri::IDENTIFIER) {	
			return $this->setCondition(new Property\IdentifierProperty(ObjectUri::IDENTIFIER, null, null, $mode));	
		} elseif (($property = $this->_do->getProperty($propertyName)) !== false) {
			return $this->setCondition($property, null, null, $mode);
		} elseif (strstr($propertyName, '.') !== false) {		
			// deal with recursive properties
			$parts = explode('.', $propertyName);
			$condition = $this->setCondition($this->_do->getProperty($parts[0]));
	
			foreach (array_slice($parts,1) as $property) {
				$condition = $condition->having($property);
			}
				
			return $condition;
		}
	
		throw new Exception(["CONDITION_UNKNOWN_PROPERTY", $propertyName]);
	}
}
