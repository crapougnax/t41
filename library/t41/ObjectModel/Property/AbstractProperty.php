<?php

namespace t41\ObjectModel\Property;

use t41\ObjectModel\ObjectUri;

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

use t41\ObjectModel,
	t41\ObjectModel\DataObject,
	t41\ObjectModel\ObjectModelAbstract,
	t41\ObjectModel\Property,
	t41\Core\Tag;

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
	
	
	protected $_initialValue;
	
	
	/**
	 * Help text
	 * @var string
	 */
	protected $_helpText;
	
	
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
		$this->_setParameterObjects($paramObjs);
		
		if (is_array($params)) {
			try {
				$this->_setParameters($params);
			} catch (\Exception $e) {

				// @todo throw nice exception
				throw new Exception($e->getMessage());
			}
		}
	}
	
	
	/**
	 * Sets a reference to parent data object
	 * 
	 * @param DataObject $parent
	 * @return \t41\ObjectModel\Property\AbstractProperty
	 */
	public function setParent(DataObject $parent)
	{
		$this->_parent = $parent;
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
			
			/* defines first initialized value */
			if (! $this->_initialValue && $value != $this->getParameter('defaultvalue')) {
				$this->_initialValue = $value;
			}
		}
		
		$this->_triggerRules('after/set');
		
		return $this;
	}
	
	
	public function setHelpText($str)
	{
		$this->_helpText = $str;
		return $this;
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see t41\ObjectModel\Property.PropertyInterface::getValue()
	 */
	public function getValue($param = null)
	{	
		$this->_triggerRules('before/get');
		
		switch ($param) {
			
			case 'display':
				$value = $this->getDisplayValue();
				break;
				
			case 'default':
				$value = $this->getDefaultValue();
				break;
				
			default:
				$value = $this->_value;
				break;
		}
		$this->_triggerRules('after/get');
		return $value;
	}
	
	
	public function getDefaultValue()
	{
		return $this->getParameter('defaultvalue');
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see \t41\ObjectModel\Property\PropertyInterface::getDisplayValue()
	 */
	public function getDisplayValue()
	{
		return $this->getValue();
	}
	
	
	public function getInitialValue()
	{
		return $this->_initialValue; 
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
	
	
	public function getHelpText()
	{
		return $this->_helpText;
	}
	
	
	/**
	 * Returns the parent data object instance of the current property
	 * 
	 * @return t41\ObjectModel\DataObject
	 */
	public function getParent()
	{
		return $this->_parent;
	}
	
	
	public function hasChanged()
	{
		return (bool) $this->_changed;
	}
	
	
	/**
	 * Reset the property value to the previous stored one or the default one, if any.
	 * to set the value to NULL, use resetValue()
	 */
	public function reset()
	{
		if ($this->_initialValue) {
			$this->setValue($this->_initialValue);
		
		} else if ($this->getParameter('defaultvalue')) {
			$this->setValue($this->getParameter('defaultvalue'));
			
		} else {
			$this->resetValue();
		}
		$this->resetChangedState();
	}
	
	
	public function resetChangedState()
	{
		$this->_changed = false;
	}
	
	
	public function resetValue()
	{
		$this->_value = null;
		$this->_changed = true;
		return $this;
	}
	

	protected function _parseDisplayProperty()
	{
		$display = $this->getParameter('display');
		if (! $display) {
			$this->_displayValue = $this->getValue()->__toString();
			return true;
		}
		
		if (substr($display,0,1) == '[') {
			// mask @todo have distinct parameter ?
			Tag\ObjectTag::$object = $this->getValue();
			$this->_displayValue = Tag::parse(substr($display, 1, strlen($display)-2));
			
		} else {
			
			$displayProps = explode(',', $display);
			if (count($displayProps) == 1 && $displayProps[0] == '') {
				$this->_displayValue = Property::UNDEFINED_LABEL;
			
			} else {
			
				$this->_displayValue = array();
				foreach ($displayProps as $disProp) {
					if ($this->_value->getProperty($disProp)) {
						$this->_displayValue[] = $this->_value->getProperty($disProp)->getDisplayValue();
					} elseif ($disProp == ObjectUri::IDENTIFIER) {
						//@todo refactor this
						$this->_displayValue[] = $this->_value->getIdentifier();
					}
				}
				$this->_displayValue = implode(' ', $this->_displayValue);
			}
		}
	}
	
	
	/**
	 * Clone rules
	 * @see t41\ObjectModel.ObjectModelAbstract::__clone()
	 */
	public function __clone()
	{
		foreach ($this->_rules as $key => $rule) {
			$this->_rules[$key] = clone $rule;
		}
	}
	

	/**
	 * Function called from BaseObject::__clone() method in order to refresh object's reference in rules
	 * @param ObjectModel\BaseObject $object
	 */
	public function changeRulesObjectReference(ObjectModel\BaseObject $object)
	{
		foreach ($this->_rules as $rule) {
			$rule->setObject($object);
		}
		return $this;
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see t41\ObjectModel.ObjectModelAbstract::reduce()
	 */
	public function reduce(array $params = array(), $cache = true)
	{
		$class = get_class($this);
		$type = str_replace('Property','', substr($class, strrpos($class, '\\')+1));
		return array_merge(parent::reduce($params, $cache), array('value' => $this->getValue(), 'type' => $type));
	}
}
