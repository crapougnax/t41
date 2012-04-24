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
 * @version    $Revision: 832 $
 */

use t41\ObjectModel,
	t41\ObjectModel\Collection;

/**
 * Property class to use for object values
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
	 * Return current ObjecModel\Collection instance handled by current instance
	 * instant instanciation is performed if $_value is null
	 * 
	 *  @return \t41\ObjectModel\Collection
	 */
	public function getValue()
	{
		if (is_null($this->_value)) {

			/* set a new Collection based on instanceof parameter value */
			$this->_value = new ObjectModel\Collection($this->getParameter('instanceof'));
				
			/* inject the condition that allows to find collection members */
			if ($this->getParameter('keyprop')) {
				$this->_value->having($this->getParameter('keyprop'))->equals($this->_parent);
			}
			
			/* inject any other defined condition */
			if ($this->getParameter('morekeyprop')) {
				
				foreach ($this->getParameter('morekeyprop') as $value) {
					
					if (strstr($value, ' ') !== false) {
						
						// if value contains spaces, it's a pattern
						$parts = explode(' ', $value);
						if (count($parts) == 3) {
							
							if (strstr($parts[2],',') !== false) $parts[2] = explode(',', $parts[2]);
							$this->_value->having($parts[0])->$parts[1]($parts[2]);
						
						} else {
							if (strstr($parts[1],',') !== false) $parts[1] = explode(',', $parts[1]);
							$this->_value->having($parts[0])->equals($parts[1]);
						}
						
					} else {
						
						// default case, we expect the member to hold a property
						// with the same name and value as the current object
						$this->_value->having($value)->equals($this->_parent->getProperty($value)->getValue());
					}
				}
			}
		}
		
		return parent::getValue();
	}
	
	
	public function reduce(array $params = array())
	{
		$value = $this->getValue();
		$value->find();
		$value = $value->reduce($params);
		return array_merge(parent::reduce($params), array('value' => $value['collection'], 'type' => 'Collection'));
	}
}
