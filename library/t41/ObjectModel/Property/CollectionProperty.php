<?php

namespace t41\ObjectModel\Property;

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
 * @package    t41_Property
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

use t41\ObjectModel;
use t41\ObjectModel\Collection;
use t41\Backend\Condition;
use t41\Core;
use t41\ObjectModel\ObjectUri;

/**
 * Property class to manipulate collections of objects
 *
 * @category   t41
 * @package    t41_Property
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class CollectionProperty extends AbstractProperty {

	
	/**
	 * Collection instance
	 * 
	 * @var t41\ObjectModel\Collection
	 */
	protected $_value;
	
	
	/**
	 * Append (default), prepend or remove a member from the collection
	 * 
	 * @todo inconsistant: value should be a collection and individual members added from object
	 * 
	 * @param t41\ObjectModel\BaseObject $value
	 * @param string $action
	 * @return boolean
	 */
	public function setValue($value, $action = Collection::MEMBER_APPEND)
	{
		$res = true;
		
		// instanciate collection jit
		if (is_null($this->_value)) {
			$this->getValue();
		}
		
		switch ($action) {

			case Collection::MEMBER_APPEND:
			case Collection::MEMBER_PREPEND:
				
				// check that new member abides by optional unicity parameter (which contains a property name)
				if ($this->getParameter('unique')) {
					
					$token = $value->getProperty($this->getParameter('unique'))->getValue();

					// try to find an existing record with same values
					$reqcol = clone $this->getValue();
					$reqcol->having($this->getParameter('unique'))->equals($token);
					if($reqcol->getMax() > 0) {
						
						return false;
					}
					
					// @todo also test members not already saved or save before testing !
				}
				
				// $value should contain a property where to store the relation
				$value->getProperty($this->getParameter('keyprop'))->setValue($this->_parent);
				
				$this->_value->addMember($value, $action);
				break;
				
			case Collection::MEMBER_REMOVE:
				$res = $this->_value->removeMember($value);
				break;
		}

		$this->_changed = true;
		return true;
	}
	
	
	/**
	 * Returns the number of members in the collection
	 * @see \t41\ObjectModel\Property\AbstractProperty::getDisplayValue()
	 */
	public function getDisplayValue()
	{
	    return $this->getValue()->count();
	}
	
	
	/**
	 * Return current ObjecModel\Collection instance handled by current instance
	 * instant instanciation is performed if $_value is null or $force is true
	 * 
	 *  @param boolean $force
	 *  @return t41\ObjectModel\Collection
	 */
	public function getValue($force = false)
	{
		if (is_null($this->_value) || $force === true) {
			/* set a new Collection based on instanceof parameter value */
			$this->_value = new ObjectModel\Collection($this->getParameter('instanceof'));
			$this->_value->setBoundaryBatch(-1);
			$this->_value->setParent($this);
				
			/* inject the condition that allows to find collection members */
			if ($this->getParameter('keyprop')) {
				$this->_value->having($this->getParameter('keyprop'))->equals($this->_parent);
			}
			
			/* inject any other defined condition */
			if ($this->getParameter('morekeyprop')) {
				foreach ($this->getParameter('morekeyprop') as $value) {
					if (strstr(trim($value), ' ') !== false) {
						// if value contains spaces, it's a pattern
						$parts = explode(' ', $value);
						if (count($parts) == 3) {
							if ($parts[2] == 'novalue') $parts[2] = Condition::NO_VALUE;
							if ($parts[2] == ObjectUri::IDENTIFIER) {
							    $parts[2] = $this->_parent->getUri();
							}
							if (substr($parts[2],0,1) == '%' && substr($parts[2], -1) == '%') {
								$prop = substr($parts[2], 1, strlen($parts[2])-2);
								if (($prop = $this->_parent->getProperty($prop)) !== false) {
									$parts[2] = $prop->getValue();
								}
							}
							if (strstr($parts[2],',') !== false) $parts[2] = explode(',', $parts[2]);
							$this->_value->having($parts[0])->$parts[1]($parts[2]);
						} else {
							if ($parts[1] == 'novalue') $parts[1] = Condition::NO_VALUE;
							if (substr($parts[1],0,1) == '%' && substr($parts[1], -1) == '%') {
								$prop = substr($parts[1], 1, strlen($parts[1])-2);
								if (($prop = $this->_parent->getProperty($prop)) !== false) {
									$parts[1] = $prop->getValue();
								}
							}
							if (strstr($parts[1],',') !== false) $parts[1] = explode(',', $parts[1]);
							$this->_value->having($parts[0])->equals($parts[1]);
						}
						
					} else {
						// default case, we expect the member to hold a property
						// with the same name and value as the current object
						if (($property = $this->_parent->getProperty($value)) === false) {
							throw new Exception(sprintf("keyprop value '%s' doesn't match any property of class '%s'"
							    , $value, $this->_parent->getClass()));
						}
						$this->_value->having($value)->equals($property->getValue());
					}
				}
			}
			
			// set sorting
			if ($this->getParameter('sorting')) {
				foreach ($this->getParameter('sorting') as $key => $val) {
					if ($this->_value->getDataObject()->getRecursiveProperty($key) !== false) {
						$this->_value->setSorting(array($key, $val));
					} else {
						Core::log(sprintf("Can't sort %s property with unknown property %s", $this->_id, $key), \Zend_Log::WARN);
					}
				}
			}
			// DON'T POPULATE THERE, IT IS DONE IMPLICITELY IN Collection::getMembers()
			//$this->_value->debug();
		}
		return parent::getValue();
	}
	
		
	public function __clone()
	{
		$this->_value = null;
	}
	
	
	public function reduce(array $params = array(), $cache = true)
	{
		if (isset($params['collections']) && $params['collections'] > 0) {
			$value = $this->getValue();
			$value = $value->reduce($params, $cache);
		}
		
		return array_merge( parent::reduce($params), 
							array('value' => isset($value) ? $value['collection'] : $this->_value, 'type' => 'Collection'));
	}
}
