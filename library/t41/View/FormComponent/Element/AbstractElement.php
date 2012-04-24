<?php

namespace t41\View\FormComponent\Element;

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
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 876 $
 */

use t41\Parameter,
	t41\Backend,
	t41\View,
	t41\View\Action;

/**
 * t41 Data Object handling a set of properties tied to an object
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
abstract class AbstractElement extends View\ViewObject {
	

	const CONSTRAINT_PASSWORD	=	'password';

	const CONSTRAINT_HIDDEN		=	'hidden';
	
	
	/**
	 * Field's value inherited constraints
	 *
	 * @var array
	 */
	protected $_is = array();

	/**
	 * Others field's value constraints
	 *
	 * @var array
	 */
	protected $_has = array();
	
	
	protected $_value;
	
	protected $_defaultVal;
	
	protected $_enumValues;

	protected $_helpText;
	
	protected $_altId;
	
	protected $_conditions = array();
	
	protected $_positions = array();
	
	
	public function hide()
	{
		$this->_is[self::CONSTRAINT_HIDDEN] = true;
		return $this;
	}
	
	
	public function show()
	{
		$this->_is[self::CONSTRAINT_HIDDEN] = false;
		return $this;
	}

	
	public function readOnly()
	{
		$this->_is[self::CONSTRAINT_PROTECTED] = true;
		return $this;
	}
	

	public function readWrite()
	{
		$this->_is[self::CONSTRAINT_PROTECTED] = false;
		return $this;
	}
	
	
	public function getSearchMode()
	{
		return $this->_is['searchable'];
	}
	
	
	/**
	 * Return the field name part of the id (after the point)
	 *
	 * @return string
	 */
	public function getFieldName()
	{
		return substr($this->_id, strpos($this->_id, '.')+1);
	}
	
	
	public function setEnumValues($str = null)
	{
		$this->_enumValues = $str;
		return $this;
	}
	
	
	public function setId($id)
	{		
		$this->_altId = str_replace('.', '___', $id);
		$this->fieldId = $this->_altId;
		
		return parent::setId($id);
	}
	
	public function setValue($val)
	{
		$this->_value = $val;
	}
	
	
	public function setHelpText($str)
	{
		$this->_helpText = $str;
	}
	
	
	public function getHelpText()
	{
		return $this->_helpText;
	}
	
	
	public function setDefaultValue($val)
	{
		 $this->_defaultVal = $val;
	}
	
	
	public function getEnumValues()
	{
		return $this->_enumValues;
	}
	
	
	public function setConstraint($constraint, $val)
	{
		$this->_is[$constraint] = $val;
		
		return $this;
	}
	
	
	/**
	 * Returns the boolean status for the constraint index key provided
	 *
	 * @param string $constraint
	 * @return boolean
	 */
	public function getConstraint($constraint)
	{
		if (isset($this->_is[$constraint])) {
			
			return $this->_is[$constraint];
		} else {
			
			return false; // 'N';
		}
	}
	
	
	public function getValueConstraint($constraint)
	{
		if (isset($this->_has[$constraint])) {
			
			return $this->_has[$constraint];
		} else {
			
			return false;
		}		
	}
	
	
	public function formatValue($val = null)
	{
		return $val;
	}
	
	
	/**
	 * Returns either element value or default value if setted and $useDefault is true
	 * 
	 * @param boolean $useDefault
	 * @return mixed
	 */
	public function getValue($useDefault = true)
	{
		if (isset($this->_value)) {
			
			return $this->_value;
			
		} else if ($useDefault === true) {
			
			return $this->_defaultVal;
		}
	}
	
	
	public function getAltId($str = null)
	{
		return $str . $this->_altId;
	}
	
	
	public function getShortId() {
		
		return substr($this->_id, strpos($this->_id, '.')+1);
	}
	
	
	
	/**
	 * Set a condition applicable when selection of possible field values occur.
	 * If the field is of Foreign or Multiple Key type, $field parameter is a field of the foreign table/object
	 * @param mixed $val
	 * @param string $operator
	 * @param string $field
	 */
    public function setCondition($val, $operator = Backend\Condition::CONDITION_EQUAL, $field = null)
    {                
        $this->_conditions[] = array('val' => $val, 'operator' => $operator, 'obj' => $field);
        return true;
    }
	
    
    /**
     * Define this method in complex field type which content saving can't be handled by basic save method
     * 
     * @param array $data Data to proceed
     */
    public function saveData($data)
    {
    	return true;
    }
    
    
    public function setAction(Action\AbstractAction $action)
    {
    	$this->_action = $action;
    	return $this;
    }
    
    
    public function getAction()
    {
    	return $this->_action;
    }
}
