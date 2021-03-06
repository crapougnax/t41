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


/**
 * Abstract class providing basic t41_Property_* objects methods
 *
 * @category   t41
 * @package    t41_Property
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
abstract class PropertyAbstract extends \t41\ObjectModel\ObjectModelAbstract implements PropertyInterface {
	
	
	/**
	 * Parameter value
	 * 
	 * @var $_value mixed
	 */
	protected $_value;
	
	
	/**
	 * Property parent (either t41_Object_Model or t41_Property_Abstract instance)
	 * 
	 * @var $_parent t41_Object_Model|t41_Property_Abstract
	 */
	protected $_parent;
	
	
	protected $_rules;
	
	
	/**
	 * Changed status, set to true when setValue() is called
	 *  
	 * @var boolean
	 */
	protected $_changed = false;
	
/*	
	public function __construct($id, array $params = null, array $paramObjs = null)
	{
		$this->setId($id);
		
		if (is_array($paramObjs)) {
			
			$this->_setParameterObjects($paramObjs);
		}
		
		if (is_array($params)) {
			$this->_setParameters($params);
		}
	}
*/	
	
	public function setValue($value)
	{
		if (is_array($this->getParameter('validators'))) {

			foreach ($this->getParameter('validators') as $validator => $namespace) {
				
				if (! \Zend_Validate::is($value, $validator)) { //, array(), $namespace)) {
					
					$exMsgid = "VALUE_VALIDATOR_" . strtoupper($validator) . "_FAILED";
					throw new Exception(array($exMsgid, $value));
				}
			}
		}
		
		
		$this->_value = $value;
		$this->_changed = true;
		
		return $this;
	}
	
	
	public function getValue()
	{
		return $this->_value;
	}
	
	
	public function getDefaultValue()
	{
		return $this->getParameter('defaultvalue');
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
	
	
	public function __clone()
	{
		if (is_object($this->_value)) {
			$this->_value = clone $this->_value;
		}
	}
}