<?php
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
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 832 $
 */

/** Required files */
require_once 't41/Property/Abstract.php';

/**
 * Property class to use for object values
 *
 * @category   t41
 * @package    t41_Property
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class t41_Property_Collection extends t41_Property_Abstract {

	
	/**
	 * Collection instance
	 * 
	 * @var t41_Object_Collection
	 */
	protected $_value;
	
	public function setValue($value, $action = 'add')
	{
		switch ($action) {

			case 'add':
				$this->_value->addMember($value);
				break;
				
			case 'replace':
				break;
				
			case 'remove':
				break;
		}
//		parent::setValue($value);
	}
	
	
	public function __call($method, $arguments)
	{
		if (! method_exists($this->_value, $method)) {
			
			throw new t41_Property_Exception("PROPERTY_UNKNOWN_METHOD", $method);
		}
		
		if (is_array($this->_rules)) {
			
			if (isset($this->_rules[$method])) {
				
				foreach ($this->_rules[$method] as $rule) {
					
					$rule->execute($this, $rule['destination']);
				}
			}
		}
		
		return $this->_value->$method($arguments);
	}
	
	
	/**
	 * Return current t41_Object_Collection instance handled by current instance
	 * instant instanciation is performed is $_value is null
	 * 
	 *  @return t41_Object_Collection
	 */
	public function getValue()
	{
		if (is_null($this->_value)) {

			/* set a new t41_Object_Collection based on instanceof parameter value */
			$this->_value = new t41_Object_Collection(t41_Data::factory($this->getParameter('instanceof')));
			
			/* inject the condition that allows to find collection members */
			$this->_value->having($this->getParameter('keyprop'))->equals($this->_parent);
		}
		
		return parent::getValue();
	}
}