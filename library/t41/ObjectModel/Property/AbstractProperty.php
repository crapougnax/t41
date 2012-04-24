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
 * @version    $Revision: 865 $
 */

use t41\ObjectModel\ObjectModelAbstract,
	t41\ObjectModel;

/**
 * Abstract class providing basic t41\ObjectModel\Property\*Property objects methods
 *
 * @category   t41
 * @package    t41_Property
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
abstract class AbstractProperty extends ObjectModelAbstract implements PropertyInterface {
	
	
	/**
	 * Parameter value
	 * 
	 * @var $_value mixed
	 */
	protected $_value;
	
	
	/**
	 * Property parent (either t41\ObjectModel\BaseObject or t41\ObjectModel\AbstractProperty instance)
	 * 
	 * @var $_parent t41_Object_Model|t41_Property_Abstract
	 */
	protected $_parent;
	
	
	/**
	 * Array of rules observers
	 * @var array
	 */
	protected $_rules = array();
	
	
	/**
	 * Changed status, switched to true when setValue() is called and value changes
	 *  
	 * @var boolean
	 */
	protected $_changed = false;
	
	
	public function __construct($id, array $params = null, array $paramObjs = null)
	{
		$this->setId($id);
		
		if (is_array($paramObjs)) {
			
			$this->_setParameterObjects($paramObjs);
		}
		
		if (is_array($params)) {
			try {
				$this->_setParameters($params);
			} catch (\Exception $e) {

				// @todo throw nice exception
				\Zend_Debug::dump($params); die;
			}
		}
	}
	
	
	public function setParent($parent)
	{
		$this->_parent = $parent;
	}
	
	
	/**
	 * Attach a rule instance and its trigger to the property
	 * @param ObjectModel\Rule\AbstractRule $rule
	 * @param unknown_type $trigger
	 */
	public function attach(ObjectModel\Rule\AbstractRule $rule, $trigger)
	{
		if (! isset($this->_rules[$trigger]) || ! is_array($this->_rules[$trigger])) {
			
			$this->_rules[$trigger] = array();
		}
		$this->_rules[$trigger][] = $rule;
		return $this;
	}
	
	
	public function setValue($value)
	{
		if ($this->getParameter('constraint.protected') == true && ! is_null($this->_value)) {
			
			throw new Exception(array("VALUE_IS_PROTECTED", $this->_id));
		}
		
		if (is_array($this->getParameter('validators'))) {

			foreach ($this->getParameter('validators') as $validator => $namespace) {
				
				if (! \Zend_Validate::is($value, $validator)) { //, array(), $namespace)) {
					
					$exMsgid = "VALUE_VALIDATOR_" . strtoupper($validator) . "_FAILED";
					throw new Exception(array($exMsgid, $value));
				}
			}
		}
		
		$this->_triggerRules('before/set');
		
		if ($value !== $this->_value) {
			$this->_changed = true;
			$this->_value = $value;
		}
		
		$this->_triggerRules('after/set');
		
		return $this;
	}
	
	
	public function getValue()
	{		
		$this->_triggerRules('before/get');
		$value = $this->_value;
		$this->_triggerRules('after/get');
		return $value;
	}
	
	
	public function getDefaultValue()
	{
		return $this->getParameter('defaultvalue');
	}
	
	
	public function getDisplayValue()
	{
		return $this->getValue();
	}
	
	
	public function getLabel($lang = null)
	{
		$label = $this->getParameter('label');
		
		if (! is_array($label)) {
			
			return $label;
		}
		
		if ($lang && isset($label[$lang])) {

			return $label[$lang];
		
		} else if (isset($label['en'])) {
			
			return $label['en'];
		} else {
			
			return null;
		}
	}
	

	/**
	 * Returns the parent t41_Property_* instance of the current property
	 * 
	 * @return t41_Property_Abstract
	 */
	public function getParent()
	{
		return $this->_parent;
	}
	
	
	public function hasChanged()
	{
		return $this->_changed;
	}
	
	
	public function resetChangedState()
	{
		$this->_changed = false;
	}
	
	
	/**
	 * Execute defined rules for given trigger
	 *
	 * @param string $trigger
	 * @return boolean
	 */
	protected function _triggerRules($trigger)
	{
		if (! isset($this->_rules[$trigger])) {
				
			return true;
		}
	
		$result = true;
	
		foreach ($this->_rules[$trigger] as $rule) {
	
			$result = $result && $rule->execute($this);
		}
	
		return $result;
	}
	
	
	public function __clone()
	{
		if (is_object($this->_value)) {
			$this->_value = clone $this->_value;
		}
	}
	
	
	public function reduce(array $params = array())
	{
		$class = get_class($this);
		$type = str_replace('Property','', substr($class, strrpos($class, '\\')+1));
		return array_merge(parent::reduce($params), array('value' => $this->_value, 'type' => $type));
	}
}
